<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Session;
use Carbon\Carbon;
use Exception;
use Google_Service_Exception;

class GoogleCalendarController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(base_path(env('GOOGLE_APPLICATION_CREDENTIALS')));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setRedirectUri('http://localhost:3000/auth/google-callback');
    }

    // Ahora se hace en el front
    public function redirectToGoogle()
    {
        return redirect($this->client->createAuthUrl()); // Redirige directamente a la URL de autenticación de Google
    }

    public function handleGoogleCallback(Request $request)
    {
        info('Google callback');
        info($request->all());
        if (!$request->has('code')) {
            info('No authorization code no received');
            return response()->json(['error' => 'No authorization code received'], 400);
        }

        try {
            info('solicitando token');
            info('Redirect URI set in client: ' . $this->client->getRedirectUri());
            $token = $this->client->fetchAccessTokenWithAuthCode($request->input('code'));
            info('token: ' . json_encode($token));
            if (isset($token['error'])) {
                info('Failed to authenticate with Google: ' . $token['error']);
                return response()->json(['error' => 'Failed to authenticate with Google: ' . $token['error']], 401);
            }
            info('Token received: ' . json_encode($token));
            $user = auth()->user(); // Asegurar de que el usuario está autenticado antes de ejecutar esto

            info('Updating user with: ', [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'expires_at' => Carbon::now()->addSeconds($token['expires_in']),
            ]);

            $user->update([
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'],
                'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in']),
                'is_google_auth_completed' => true
            ]);

            info('User updated: ', [
                'google_access_token' => $user->google_access_token,
                'google_refresh_token' => $user->google_refresh_token,
                'google_token_expires_at' => $user->google_token_expires_at,
                'is_google_auth_completed' => $user->is_google_auth_completed,
            ]);

            info('Successfully authenticated with Google');
            info('User info: ' . json_encode($user));

            return response()->json(['message' => 'Successfully authenticated with Google']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to authenticate with Google: ' . $e->getMessage()], 500);
        }
    }


    public function createEvent($reservation)
    {
        info('Creating event for reservation: ' . json_encode($reservation));
        $user = auth()->user();
        info('User: ' . json_encode($user));

        if ($user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }

        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now())
        ]);

        if ($this->client->isAccessTokenExpired()) {
            info('Token expired proceeding to refresh');
            $this->refreshToken($user);
        }

        $calendarService = new Google_Service_Calendar($this->client);

        $colorId = 20;

        info('Creating event in Google Calendar');
        $event = new Google_Service_Calendar_Event([
            'summary' => 'Reserva para ' . $reservation->name . $colorId,
            'description' => 'Detalles de la reserva...',
            'start' => [
                'date' => Carbon::parse($reservation->date)->toDateString(), // Formato YYYY-MM-DD
                'timeZone' => 'America/Costa_Rica',
            ],
            'end' => [
                'date' => Carbon::parse($reservation->date)->addDays($reservation->nights)->toDateString(), // Fecha de fin debe ser +1 día del último día completo
                'timeZone' => 'America/Costa_Rica',
            ],
            'attendees' => [
                ['email' => 'persona1@example.com'],
                ['email' => 'persona2@example.com']
            ],
            'colorId' => $colorId,//$this->getColorId($reservation->cabin),
            'guestsCanModify' => false,
        ]);

        info('Event details: ' . json_encode($event));

        $calendarService2 = new Google_Service_Calendar($this->client);
    try {
        $colors = $calendarService2->colors->get();
        info('Available colors: ' . json_encode($colors));
        // return $colors;
    } catch (Exception $e) {
        info('Error fetching colors: ' . $e->getMessage());
        return null;
    }

        try {
            $createdEvent = $calendarService->events->insert(env('GOOGLE_CALENDAR_ID'), $event);
            info('Event created successfully: ' . json_encode($createdEvent));
            return redirect()->back()->with('status', 'Reserva y evento de calendario creados con éxito!');
        } catch (Google_Service_Exception $e) {
            $errors = $e->getErrors();
            info('Error creating event: ' . json_encode($errors));
            return redirect()->back()->with('error', 'Error al crear el evento en el calendario: ' . json_encode($errors));
        } catch (Exception $e) {
            info('Error creating event: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear el evento en el calendario: ' . $e->getMessage());
        }
    }

    private function refreshToken($user)
    {
        if ($this->client->isAccessTokenExpired()) {
            $refreshedToken = $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
            $user->update([
                'google_access_token' => $refreshedToken['access_token'],
                'google_token_expires_at' => Carbon::now()->addSeconds($refreshedToken['expires_in'])
            ]);
        }
    }


    private function getColorId($cabin)
    {
        $colors = [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
        ];
        return $colors[$cabin] ?? '1';
    }
}
