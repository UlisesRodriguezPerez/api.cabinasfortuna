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
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI') . '/auth/google-callback');
    }

    // Ahora se hace en el front
    public function redirectToGoogle()
    {
        return redirect($this->client->createAuthUrl()); // Redirige directamente a la URL de autenticación de Google
    }

    public function handleGoogleCallback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json(['error' => 'No authorization code received'], 400);
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($request->input('code'));
            if (isset($token['error'])) {
                return response()->json(['error' => 'Failed to authenticate with Google: ' . $token['error']], 401);
            }
            $user = auth()->user(); // Asegurar de que el usuario está autenticado antes de ejecutar esto

            $user->update([
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'],
                'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in']),
                'is_google_auth_completed' => true
            ]);

            return response()->json(['message' => 'Successfully authenticated with Google']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to authenticate with Google: ' . $e->getMessage()], 500);
        }
    }


    public function createEvent($reservation)
    {
        $user = auth()->user();

        if ($user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }

        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now())
        ]);

        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken($user);
        }

        $calendarService = new Google_Service_Calendar($this->client);

        $numberCabin = $reservation->cabin;
        $persons = $reservation->adults + $reservation->children;
        $adults = $reservation->adults;
        $children = $reservation->children;
        $nights = $reservation->nights;
        $totalAmountUSD = $reservation->amountUSD;
        $totalAmountCRC = $reservation->amountCRC;
        $clientName = $reservation->name;
        $phoneNumber = $reservation->phoneNumber ?? 'N/A';
        $agency = $reservation->agency;
        $commission = $reservation->commission ?? 0;
        $paidToUlisesUSD = $reservation->paidToUlisesUSD ?? 0;
        $paidToDeyaniraUSD = $reservation->paidToDeyaniraUSD ?? 0;
        $paidToUlisesCRC = $reservation->paidToUlisesCRC ?? 0;
        $paidToDeyaniraCRC = $reservation->paidToDeyaniraCRC ?? 0;
        $invoiceNeeded = $reservation->invoiceNeeded ?? false;
        $paidToDeyanira = $reservation->paidToDeyanira ?? false;
        $pendingToPay = $reservation->pendingToPay ?? false;
        $pendingAmountUSD = $reservation->pendingAmountUSD ?? 0;
        $pendingAmountCRC = $reservation->pendingAmountCRC ?? 0;
        $note = $reservation->note ?? 'N/A';
        $personsText = $adults > 1 ? 'personas' : 'persona';

        $description = "{$clientName}\n"
            . "{$adults} adultos\n"
            . "{$children} niños\n"
            . "{$nights} noches\n"
            . ($totalAmountUSD ? "Monto total: {$totalAmountUSD} USD\n" : "")
            . ($totalAmountCRC ? "Monto total: {$totalAmountCRC} CRC\n" : "")
            . ($paidToUlisesUSD ? "Pagó a Ulises: {$paidToUlisesUSD} USD\n" : "")
            . ($paidToUlisesCRC ? "Pagó a Ulises: {$paidToUlisesCRC} CRC\n" : "")
            . ($paidToDeyanira ? "Pagó a Deyanira:\n" : "")
            . ($paidToDeyaniraUSD ? "    - {$paidToDeyaniraUSD} USD\n" : "")
            . ($paidToDeyaniraCRC ? "    - {$paidToDeyaniraCRC} CRC\n" : "")
            . ($pendingToPay ? "Pendiente:\n" : "")
            . ($pendingAmountUSD ? "    - {$pendingAmountUSD} USD\n" : "")
            . ($pendingAmountCRC ? "    - {$pendingAmountCRC} CRC\n" : "")
            . ($agency ? "Agencia: {$agency}\n" : "")
            . ($commission ? "Comisión: {$commission}\n" : "")
            . ($invoiceNeeded ? "Factura requerida: " . ($invoiceNeeded ? 'Sí' : 'No') . "\n" : "")
            . "Teléfono: {$phoneNumber}\n"
            . "Nota: {$note}";

        $event = new Google_Service_Calendar_Event([
            'summary' => "Cabina #{$numberCabin}, {$persons} {$personsText}, {$nights} noches",
            'description' => $description,
            'start' => [
                'date' => Carbon::parse($reservation->date)->toDateString(), // Formato YYYY-MM-DD
                'timeZone' => 'America/Costa_Rica',
            ],
            'end' => [
                'date' => Carbon::parse($reservation->date)->addDays($reservation->nights)->toDateString(), // Fecha de fin debe ser +1 día del último día completo
                'timeZone' => 'America/Costa_Rica',
            ],
            'attendees' => [
                ['email' => 'deyanirap862@gmail.com'],
                ['email' => 'lisrp.97@gmail.com']
            ],
            'colorId' => $this->getColorId($reservation->cabin),
            'guestsCanModify' => false,
        ]);

        try {
            $createdEvent = $calendarService->events->insert(env('GOOGLE_CALENDAR_ID'), $event, ['sendUpdates' => 'all']);
            // Guarda el ID del evento en la reserva
            $reservation->update(['google_event_id' => $createdEvent->getId()]);
            return response()->json(['status' => 'success', 'message' => 'Reserva y evento de calendario creados con éxito!']);

        } catch (Google_Service_Exception $e) {
            $errors = $e->getErrors();
            return redirect()->back()->with('error', 'Error al crear el evento en el calendario: ' . json_encode($errors));
        } catch (Exception $e) {
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
            1 => '11', // Red
            2 => '5', // Yellow
            3 => '3', // Purple
            4 => '10', // Green
            5 => '7', // Light blue
        ];
        return $colors[$cabin] ?? '1';
    }
}
