<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ReservationsExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportReservations()
    {
        return Excel::download(new ReservationsExport, 'reservations-reports.xlsx');
    }

    public function showReport()
    {
        $export = new ReservationsExport();
        $data = $export->prepareCabinData(); 
        $cabins = $data; // Asumiendo que tienes un método para preparar los datos
        return view('exports.cabins', ['cabins' => $cabins]);
    }
}
