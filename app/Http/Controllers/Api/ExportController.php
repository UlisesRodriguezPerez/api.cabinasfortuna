<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ReservationsExport;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportReservations()
    {
        // return Excel::download(new ReservationsExport, 'reservations-reports.xlsx');
        $export = new ReservationsExport;
        $filename = 'reservations-reports.xlsx';
        $file = Excel::raw($export, ExcelExcel::XLSX);

        // Crear una respuesta con el archivo para enviar, no para descargar directamente
        return response($file, 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function showReport()
    {
        $export = new ReservationsExport();
        $data = $export->prepareCabinData();
        $cabins = $data; // Asumiendo que tienes un método para preparar los datos
        return view('exports.cabins', ['cabins' => $cabins]);
    }
}
