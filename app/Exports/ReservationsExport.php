<?php

namespace App\Exports;

use App\Models\Reservation;
use App\Models\CabinExpense;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


// class ReservationsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize, WithEvents
class ReservationsExport implements FromView
{
    // use Exportable;

    public function prepareCabinData()
    {
        $cabins = [];
        $generalPaidToDeyaniraUSD = 0;
        $generalPaidToDeyaniraCRC = 0;
        $generalCost = 0;
        $generalCleaningCost = 0;
        $general50PorcentUSD = 0;
        $general50PorcentCRC = 0;
        $generalTotalUSD = 0;
        $generalTotalCRC = 0;



        for ($cabinId = 1; $cabinId <= 5; $cabinId++) {
            // Obtener reservaciones por cabina
            $reservations = Reservation::where('cabin', $cabinId)
                ->where('status', '!=', 'Cancelada')
                ->where('status', '!=', 'Pendiente')
                ->where('monthYearClosing', null)
                ->get();

            $totalUSD = 0;
            $totalCRC = 0;
            $totalCommission = 0;
            $totalInvoice = 0;
            $totalUSDWithoutBookinCommission = 0;
            $comission20PorcentUSD = 0;
            $comission20PorcentCRC = 0;
            $totalUSDWithoutCommission20Porcent = 0;
            $totalCRCWithoutCommission20Porcent = 0;
            $totalPaidToDeyaniraUSD = 0;
            $totalPaidToDeyaniraCRC = 0;

            foreach ($reservations as $reservation) {
                $totalUSD += $reservation->amountUSD;
                $totalCRC += $reservation->amountCRC;
                $totalCommission += $reservation->commission;
                $totalPaidToDeyaniraUSD += $reservation->paidToDeyaniraUSD;
                $totalPaidToDeyaniraCRC += $reservation->paidToDeyaniraCRC;
                $totalInvoice += $reservation->invoice;
            }


            $totalUSDWithoutBookinCommission = $totalUSD - $totalCommission;

            $comission20PorcentUSD = $totalUSDWithoutBookinCommission * 0.2;
            $comission20PorcentCRC = $totalCRC * 0.2;

            $totalUSDWithoutCommission20Porcent = $totalUSDWithoutBookinCommission - $comission20PorcentUSD;
            $totalCRCWithoutCommission20Porcent = $totalCRC - $comission20PorcentCRC;

            // Obtener gastos por cabina
            $expenses = CabinExpense::where('cabin_name', $cabinId)->first();
            // Asegurar que cada lista de reservaciones es una colección
            $cabins[$cabinId] = new Collection([
                'reservations' => new Collection(),
                'expenses' => new Collection(),
            ]);

            // Agregar los datos a la colección con cálculos
            foreach ($reservations as $reservation) {
                $cabin = '';
                $date = $reservation->date;
                $client = $reservation->name;
                $usd = $reservation->amountUSD;
                $crc = $reservation->amountCRC;
                $platform = $reservation->agency;
                $commission = '';
                $invoice = '';
                $typeUsdChange = ($reservation->CHANGE_COLON_TO_DOLLAR + $reservation->CHANGE_DOLLAR_TO_COLON) / 2;
                $paidTo = 'Pagado a Ulises';

                // if ($reservation->agency == 'Airbnb' || $reservation->agency == 'VRBO') {
                //     $totalUSD += $usd;
                // } else
                if ($reservation->agency == 'Booking') {
                    $commission = $reservation->commission;
                }

                // check if has invoice
                if ($reservation->invoiceNeeded) {
                    // check if paid in USD
                    if ($reservation->amountUSD > 0) {
                        // calcular el iva que ya esta incluido en el monto total
                        $iva = $reservation->amountUSD / 1.13 * 0.13;
                        $invoice = $iva * $typeUsdChange;
                    } else {
                        $invoice = $reservation->amountCRC / 1.13 * 0.13;
                    }
                }


                // Check if paid to both
                if ($reservation->paidToDeyanira && ($reservation->paidToUlisesCRC > 0
                    || $reservation->paidToUlisesUSD > 0)) {
                    // create 2 rows
                    $cabins[$cabinId]['reservations']->push([
                        'Cabin Name' => $cabin,
                        '' => '',
                        'Date' => $date,
                        'Client' => $client,
                        'USD' => $usd,
                        'CRC' => $crc,
                        'Platform' => $platform,
                        'Paid To' => $paidTo,
                        'Commission' => $commission,
                        'invoice' => $invoice
                    ]);

                    $cabins[$cabinId]['reservations']->push([
                        'Cabin Name' => $cabin,
                        '' => '',
                        'Date' => $date,
                        'Client' => $client,
                        'USD' => $usd,
                        'CRC' => $crc,
                        'Platform' => $platform,
                        'Paid To' => 'Pagado a Deyanira',
                        'Commission' => $commission,
                        'invoice' => $invoice
                    ]);
                } else {
                    // When paid to Deaynira
                    if ($reservation->paidToDeyanira) {
                        $cabins[$cabinId]['reservations']->push([
                            'Cabin Name' => $cabin,
                            '' => '',
                            'Date' => $date,
                            'Client' => $client,
                            'USD' => $usd,
                            'CRC' => $crc,
                            'Platform' => $platform,
                            'Paid To' => 'Pagado a Deyanira',
                            'Commission' => $commission,
                            'invoice' => $invoice
                        ]);
                    } else {
                        // When paid to Ulises
                        $cabins[$cabinId]['reservations']->push([
                            'Cabin Name' => $cabin,
                            '' => '',
                            'Date' => $date,
                            'Client' => $client,
                            'USD' => $usd,
                            'CRC' => $crc,
                            'Platform' => $platform,
                            'Paid To' => 'Pagado a Ulises',
                            'Commission' => $commission,
                            'invoice' => $invoice
                        ]);
                    }
                }
            }

            $costLightHouse = $expenses->extra_house_light_cost;
            $costCleaning = $expenses->cleaning_cost;
            $costLightCabin = $expenses->electricity_cost;
            $costInternet = $expenses->internet_cost;
            $costOther = $expenses->other_expenses;
            $costInvoice = $totalInvoice;
            $totalCost = $costLightHouse + $costCleaning + $costLightCabin + $costInternet + $costOther + $costInvoice;

            $totalUSDWithoutCost = $totalUSDWithoutCommission20Porcent;
            $totalCRCWithoutCost = $totalCRCWithoutCommission20Porcent - $totalCost;


            $Porcent50USD = $totalUSDWithoutCost * 0.5;
            $Porcent50CRC = $totalCRCWithoutCost * 0.5;
            // create a new collection with the expenses
            $cabins[$cabinId]['expenses'] = new Collection([
                'costLightHouse' => $costLightHouse,
                'costCleaning' => $costCleaning,
                'costLightCabin' => $costLightCabin,
                'costInternet' => $costInternet,
                'costOther' => $costOther,
                'costInvoice' => $costInvoice,
                'totalCost' => $totalCost,
                'totalUSD' => $totalUSD,
                'totalCRC' => $totalCRC,
                'totalCommission' => $totalCommission,
                'totalInvoice' => $totalInvoice,
                'totalUSDWithoutBookinCommission' => $totalUSDWithoutBookinCommission,
                'comission20PorcentUSD' => $comission20PorcentUSD,
                'comission20PorcentCRC' => $comission20PorcentCRC,
                'totalUSDWithoutCommission20Porcent' => $totalUSDWithoutCommission20Porcent,
                'totalCRCWithoutCommission20Porcent' => $totalCRCWithoutCommission20Porcent,
                'totalPaidToDeyaniraUSD' => $totalPaidToDeyaniraUSD,
                'totalPaidToDeyaniraCRC' => $totalPaidToDeyaniraCRC,
                'totalUSDWithoutCost' => $totalUSDWithoutCost,
                'totalCRCWithoutCost' => $totalCRCWithoutCost,
                'Porcent50USD' => $Porcent50USD,
                'Porcent50CRC' => $Porcent50CRC
            ]);

            $generalPaidToDeyaniraUSD += $totalPaidToDeyaniraUSD;
            $generalPaidToDeyaniraCRC += $totalPaidToDeyaniraCRC;
            $generalCost += $totalCost;
            $generalCleaningCost += $costCleaning;
            $general50PorcentUSD += $Porcent50USD;
            $general50PorcentCRC += $Porcent50CRC;
            $generalTotalUSD += $totalUSD;
            $generalTotalCRC += $totalCRC;
        }

        // create a new collection with the general expenses
        $cabins['general'] = new Collection([
            'generalPaidToDeyaniraUSD' => $generalPaidToDeyaniraUSD,
            'generalPaidToDeyaniraCRC' => $generalPaidToDeyaniraCRC,
            'generalCost' => $generalCost,
            'generalCleaningCost' => $generalCleaningCost,
            'general50PorcentUSD' => $general50PorcentUSD,
            'general50PorcentCRC' => $general50PorcentCRC,
            'generalTotalUSD' => $generalTotalUSD,
            'generalTotalCRC' => $generalTotalCRC
        ]);

        return $cabins;
    }

    public function view(): View
    {

        $cabins = $this->prepareCabinData();

        // info(json_encode($cabins));


        return view('exports.cabins', [
            'cabins' => $cabins
        ]);
    }
}
