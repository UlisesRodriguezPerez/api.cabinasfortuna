<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
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
        $this->refreshTokensIfNeeded(auth()->user());
    }

    private function refreshTokensIfNeeded($user)
    {
        if ($this->client->isAccessTokenExpired() || $user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }
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
        $this->refreshTokensIfNeeded($user); // Llama a refrescar el token solo una vez al inicio

        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now())
        ]);

        $calendarService = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $this->buildSummary($reservation),
            'description' => $this->buildDescription($reservation),
            'start' => $this->buildEventDateTime($reservation->date, 0),
            'end' => $this->buildEventDateTime($reservation->date, $reservation->nights),
            'attendees' => [
                ['email' => 'deyanirap862@gmail.com'],
                ['email' => 'lisrp.97@gmail.com'],
                // ['email' => 'uli.rp1999@gmail.com'],
            ],
            'colorId' => $this->getColorId($reservation->cabin),
            'guestsCanModify' => false,
        ]);

        try {

            $createdEvent = $calendarService->events->insert(env('GOOGLE_CALENDAR_ID'), $event, ['sendUpdates' => 'all']);
            $reservation->update(['google_event_id' => $createdEvent->getId()]);
            return response()->json(['status' => 'success', 'message' => 'Reserva y evento de calendario creados con éxito!']);
        } catch (Google_Service_Exception $e) {
            $errors = $e->getErrors();
            throw new Exception('Error al actualizar el evento en el calendario: ' . json_encode($errors));
        } catch (Exception $e) {
            throw new Exception('Error al actualizar el evento en el calendario: ' . $e->getMessage());
        }
    }

    public function updateEvent($reservation)
    {
        if (empty($reservation->google_event_id)) {
            throw new Exception('No existe un ID de evento de Google para esta reserva.');
        }

        $user = auth()->user();
        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now())
        ]);

        $calendarService = new Google_Service_Calendar($this->client);

        try {
            // Recuperar el evento existente usando el google_event_id
            $event = $calendarService->events->get(env('GOOGLE_CALENDAR_ID'), $reservation->google_event_id);

            $event->setSummary($this->buildSummary($reservation));
            $event->setDescription($this->buildDescription($reservation));
            $event->setStart($this->buildEventDateTime($reservation->date, 0));
            $event->setEnd($this->buildEventDateTime($reservation->date, $reservation->nights));
            $event->setColorId($this->getColorId($reservation->cabin));

            $updatedEvent = $calendarService->events->update(env('GOOGLE_CALENDAR_ID'), $reservation->google_event_id, $event, ['sendUpdates' => 'all']);
            return response()->json(['status' => 'success', 'message' => 'Reserva y evento de calendario actualizados con éxito!']);
        } catch (Google_Service_Exception $e) {
            throw new \Exception('Error de Google Calendar: ' . json_encode($e->getErrors()));
        } catch (\Exception $e) {
            throw new \Exception('Error de sistema: ' . $e->getMessage());
        }
    }

    public function deleteEvent($reservation)
    {
        if (empty($reservation->google_event_id)) {
            throw new Exception('No existe un ID de evento de Google para esta reserva.');
        }

        $user = auth()->user();
        $this->client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at->diffInSeconds(Carbon::now())
        ]);

        $calendarService = new Google_Service_Calendar($this->client);

        try {
            $calendarService->events->delete(env('GOOGLE_CALENDAR_ID'), $reservation->google_event_id, ['sendUpdates' => 'all']);
            return response()->json(['status' => 'success', 'message' => 'Reserva y evento de calendario eliminados con éxito!']);
        } catch (Google_Service_Exception $e) {
            $errors = $e->getErrors();

            $errorMessage = json_encode($errors);

            // Buscamos un mensaje específico de error que indica que el recurso fue eliminado o no encontrado
            if (strpos($errorMessage, 'not found') !== false || strpos($errorMessage, 'Resource has been deleted') !== false) {
                return ['status' => 'warning', 'message' => 'El evento de Google Calendar ya no existe, se procederá con otras operaciones.'];
            }
            throw new \Exception('Error de Google Calendar: ' . json_encode($errors));
        } catch (\Exception $e) {
            throw new \Exception('Error de sistema: ' . $e->getMessage());
        }
    }

    private function buildSummary($reservation)
    {
        $persons = $reservation->adults + $reservation->children;
        $personsText = $persons > 1 ? 'personas' : 'persona';
        return "Cabina #{$reservation->cabin}, {$persons} {$personsText}, {$reservation->nights} noches";
    }

    private function buildDescription($reservation)
    {

        $ChangeDollarToColon = $reservation->CHANGE_DOLLAR_TO_COLON;
        $ChangeColonToDollar = $reservation->CHANGE_COLON_TO_DOLLAR;
        $amountCRCToUSD = $reservation->amountCRCToUSD;
        $amountUSDToCRC = $reservation->amountUSDToCRC;

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

        $description = "{$clientName}\n"
            . "{$adults} adultos\n"
            . "{$children} niños\n"
            . "{$nights} noches\n"
            . ($totalAmountUSD ? "Monto total: {$totalAmountUSD} USD (₡{$amountUSDToCRC})\n" : "")
            . ($totalAmountCRC ? "Monto total: {$totalAmountCRC} CRC (USD {$amountCRCToUSD})\n" : "")
            . ($paidToUlisesUSD ? "Pagó a Ulises: {$paidToUlisesUSD} USD\n" : "")
            . ($paidToUlisesCRC ? "Pagó a Ulises: {$paidToUlisesCRC} CRC\n" : "")
            . ($paidToDeyanira ? "Pagó a Deyanira:\n" : "")
            . ($paidToDeyaniraUSD ? "    - {$paidToDeyaniraUSD} USD\n" : "")
            . ($paidToDeyaniraCRC ? "    - {$paidToDeyaniraCRC} CRC\n" : "")
            . ($pendingToPay ? "Pendiente:\n" : "")
            . ($pendingAmountUSD ? "    - {$pendingAmountUSD} USD (₡" . ($pendingAmountUSD * $ChangeDollarToColon) . ")\n" : "")
            . ($pendingAmountCRC ? "    - {$pendingAmountCRC} CRC (USD " . ($pendingAmountCRC / $ChangeColonToDollar) . ")\n" : "")
            . ($agency ? "Agencia: {$agency}\n" : "")
            . ($commission ? "Comisión: {$commission}\n" : "")
            . ($invoiceNeeded ? "Factura requerida: " . ($invoiceNeeded ? 'Sí' : 'No') . "\n" : "")
            . "Teléfono: {$phoneNumber}\n"
            . "Nota: {$note}";
        return $description;
    }

    private function buildEventDateTime($date, $daysToAdd)
    {
        $eventDateTime = new Google_Service_Calendar_EventDateTime();
        $eventDateTime->setDate(Carbon::parse($date)->addDays($daysToAdd)->toDateString());
        $eventDateTime->setTimeZone('America/Costa_Rica');
        return $eventDateTime;
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
