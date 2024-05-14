<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\Carbon;
use Exception;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Request $request, GoogleCalendarController $googleCalendarController)
    {
        $connection = DB::connection(); // Obtiene la conexión de base de datos por defecto
        $connection->beginTransaction(); // Comienza una transacción
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phoneNumber' => 'required|string|max:255',
                'date' => 'required|date',
                'adults' => 'required|integer|min:1',
                'children' => 'required|integer',
                'cabin' => 'required|integer',
                'nights' => 'required|integer|min:1',
                'amountUSD' => 'nullable|numeric',
                'amountCRC' => 'nullable|numeric',
                'agency' => 'nullable|string',
                'commission' => 'nullable|numeric',
                'paidToUlisesUSD' => 'nullable|numeric',
                'paidToDeyaniraUSD' => 'nullable|numeric',
                'paidToUlisesCRC' => 'nullable|numeric',
                'paidToDeyaniraCRC' => 'nullable|numeric',
                'invoiceNeeded' => 'nullable|boolean',
                'paidToDeyanira' => 'nullable|boolean',
                'pendingToPay' => 'nullable|boolean',
                'pendingAmountUSD' => 'nullable|numeric',
                'pendingAmountCRC' => 'nullable|numeric',
                'note' => 'nullable|string',
            ]);

            if (!empty($data['date'])) {
                $data['date'] = Carbon::parse($data['date'])->format('Y-m-d H:i:s');
            }
            $reservation = Reservation::create($data);

            // Una vez que la reserva se guarda, delega la creación del evento de calendario al otro controlador
            $googleCalendarController->createEvent($reservation);
            $connection->commit(); // Confirma la transacción si todo es exitoso

            return response()->json(['message' => 'Reserva creada y evento de calendario añadido con éxito!'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $connection->rollBack(); // Revierte la transacción si hay un error de validación
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            $connection->rollBack(); // Revierte la transacción si hay cualquier otro error
            return response()->json(['error' => 'Error al crear la reserva: ' . $e->getMessage()], 500);
        }
    }
}
