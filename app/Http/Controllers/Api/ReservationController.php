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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{

    public function index(Request $request)
    {
        // $reservations = Reservation::all();
        // return response()->json($reservations);
        $query = Reservation::query();

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('date', [$request->startDate, $request->endDate]);
        }

        if ($request->filled('cabin')) {
            $query->where('cabin', $request->cabin);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('agency')) {
            $query->where('agency', $request->agency);
        }

        if ($request->filled('search')) {
            // Asumiendo que 'name' es un campo en el que quieras buscar
            // y puedes agregar más campos en el where como 'email', 'phone', etc.
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('note', 'like', '%' . $request->search . '%') //
                    ->orWhere('phoneNumber', 'like', '%' . $request->search . '%'); // Si también deseas buscar por teléfono
            });
        }

        $reservations = $query->get();
        return response()->json($reservations);
    }

    public function store(Request $request, GoogleCalendarController $googleCalendarController)
    {
        $connection = DB::connection(); // Obtiene la conexión de base de datos por defecto
        $connection->beginTransaction(); // Comienza una transacción
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phoneNumber' => 'nullable|string|max:255',
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
                'amountCRCToUSD' => 'nullable|numeric',
                'amountUSDToCRC' => 'nullable|numeric',
                'CHANGE_DOLLAR_TO_COLON' => 'nullable|numeric',
                'CHANGE_COLON_TO_DOLLAR' => 'nullable|numeric',
            ]);

            $fecha = Carbon::parse($request->date);
            $fecha->setTimezone('America/Costa_Rica');
            $data['date'] = $fecha->format('Y-m-d H:i:s');

            if (!empty($data['date'])) {
                $data['date'] = Carbon::parse($data['date'])->format('Y-m-d H:i:s');
            }

            $data['created_by'] = auth()->id(); // Guarda el ID del usuario autenticado

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

    public function update(Request $request, Reservation $reservation, GoogleCalendarController $googleCalendarController)
    {
        $connection = DB::connection(); // Obtiene la conexión de base de datos por defecto
        $connection->beginTransaction();

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phoneNumber' => 'nullable|string|max:255',
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
                'amountCRCToUSD' => 'nullable|numeric',
                'amountUSDToCRC' => 'nullable|numeric',
                'CHANGE_DOLLAR_TO_COLON' => 'nullable|numeric',
                'CHANGE_COLON_TO_DOLLAR' => 'nullable|numeric',
                'status' => 'required|string|in:Pendiente,Confirmada,Cancelada'
            ]);

            $fecha = Carbon::parse($request->date);
            $fecha->setTimezone('America/Costa_Rica');
            $data['date'] = $fecha->format('Y-m-d H:i:s');

            $reservation->update($data);

            // Update the Google Calendar event
            $googleCalendarController->updateEvent($reservation);

            $connection->commit();

            return response()->json(['message' => 'Reserva actualizada con éxito!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $connection->rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            $connection->rollBack();
            return response()->json(['error' => 'Error al actualizar la reserva: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Reservation $reservation, GoogleCalendarController $googleCalendarController)
    {
        // Obtiene el usuario autenticado
        $user = Auth::user();

        // Verifica si el correo electrónico del usuario coincide con el permitido
        if ($user->email !== 'uli.rp1999@gmail.com') {
            return response()->json(['error' => 'Acceso denegado. No tienes permiso para realizar esta acción.'], 403);
        }

        $connection = DB::connection();
        $connection->beginTransaction();

        try {
            $googleCalendarController->deleteEvent($reservation);

            $reservation->delete();

            $connection->commit();

            return response()->json(['message' => 'Reserva eliminada con éxito!']);
        } catch (\Exception $e) {
            $connection->rollBack();
            return response()->json(['error' => 'Error al eliminar la reserva: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|in:Pendiente,Confirmada,Cancelada'
        ]);

        try {
            $reservation->update(['status' => $validatedData['status']]);
            return response()->json(['message' => 'Estado de la reserva actualizado con éxito!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el estado de la reserva: ' . $e->getMessage()], 500);
        }
    }
}
