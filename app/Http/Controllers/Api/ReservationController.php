<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request)
    {

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

            return response()->json($reservation, 201); // Devuelve la reserva creada y un código de estado 201
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la reserva: ' . $e->getMessage()], 500);
        }
    }
}
