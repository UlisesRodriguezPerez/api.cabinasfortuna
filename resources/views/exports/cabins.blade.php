<table>

    <tbody>
        @foreach ($cabins as $cabinId => $data)
            @if ($cabinId !== 'general')
                <thead>
                    <tr style="font-weight: bold;">
                        <th>Cabina #{{ $cabinId }}</th>
                        <th></th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>USD</th>
                        <th>CRC</th>
                        <th>Platform</th>
                        <th>Paid To</th>
                        <th>Commission</th>
                        <th>Invoice</th>
                    </tr>
                </thead>
                @foreach ($data['reservations'] as $reservation)
                    <tr>
                        <td></td>
                        <td></td>
                        <td>{{ $reservation['Date'] }}</td>
                        <td>{{ $reservation['Client'] }}</td>
                        <td>{{ $reservation['USD'] }}</td>
                        <td>{{ $reservation['CRC'] }}</td>
                        <td>{{ $reservation['Platform'] }}</td>
                        <td>{{ $reservation['Paid To'] }}</td>
                        <td>{{ $reservation['Commission'] }}</td>
                        <td>{{ $reservation['invoice'] }}</td>
                    </tr>
                @endforeach
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Totales</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSD'] }}</td>
                    <td>{{ $data['expenses']['totalCRC'] }}</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalCommission'] }}</td>
                    <td>{{ $data['expenses']['totalInvoice'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Total Menos Booking</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSDWithoutBookinCommission'] }}</td>
                    <td>{{ $data['expenses']['totalCRC'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Comisión 20%</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['comission20PorcentUSD'] }}</td>
                    <td>{{ $data['expenses']['comission20PorcentCRC'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Total Menos Comisión 20%</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSDWithoutCommission20Porcent'] }}</td>
                    <td>{{ $data['expenses']['totalCRCWithoutCommission20Porcent'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Gastos:</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Luz Casa</td>
                    <td></td>
                    <td>{{ $data['expenses']['costLightHouse'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Luz Cabina</td>
                    <td></td>
                    <td>{{ $data['expenses']['costLightCabin'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Internet</td>
                    <td></td>
                    <td>{{ $data['expenses']['costInternet'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Limpiezas</td>
                    <td></td>
                    <td>{{ $data['expenses']['costCleaning'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Otros Gastos</td>
                    <td></td>
                    <td>{{ $data['expenses']['costOther'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Facturas</td>
                    <td></td>
                    <td>{{ $data['expenses']['costInvoice'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Total Gastos</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalCost'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Total Menos Gastos</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSDWithoutCost'] }}</td>
                    <td>{{ $data['expenses']['totalCRCWithoutCost'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>Total Mami</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalPaidToDeyaniraUSD'] }}</td>
                    <td>{{ $data['expenses']['totalPaidToDeyaniraCRC'] }}</td>
                </tr>
                <tr></tr>
                <tr>
                    <td></td>
                    <td>50%</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['Porcent50USD'] }}</td>
                    <td>{{ $data['expenses']['Porcent50CRC'] }}</td>
                </tr>
                <tr></tr>

                <!-- Insertar una fila separadora después de cada cabina -->
                <tr style="background-color: #FFFF00;">
                    <td colspan="9"></td>
                </tr>
            @endif
        @endforeach
        <tr></tr>
        <tr>
            <td>RESUMEN</td>
        </tr>
        @foreach ($cabins as $cabinId => $data)
            @if ($cabinId !== 'general')
                <tr>
                    <td>Cabina #{{ $cabinId }}</td>
                    <td></td>
                    <td></td>
                    <td>Dólares</td>
                    <td>Colones</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Total</td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSDWithoutCommission20Porcent'] }}</td>
                    <td>{{ $data['expenses']['totalCRCWithoutCommission20Porcent'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Gastos</td>
                    <td></td>
                    <td></td>
                    <td>{{ $data['expenses']['totalCost'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Total Menos Gastos</td>
                    <td></td>
                    <td>{{ $data['expenses']['totalUSDWithoutCost'] }}</td>
                    <td>{{ $data['expenses']['totalCRCWithoutCost'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>50%</td>
                    <td></td>
                    <td>{{ $data['expenses']['Porcent50USD'] }}</td>
                    <td>{{ $data['expenses']['Porcent50CRC'] }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Mami Tiene</td>
                    <td></td>
                    <td>{{ $data['expenses']['totalPaidToDeyaniraUSD'] }}</td>
                    <td>{{ $data['expenses']['totalPaidToDeyaniraCRC'] }}</td>
                </tr>

                <tr></tr>
            @endif
        @endforeach
        <tr>
            {{-- Use cabins['general'] --}}
            <td>Cabinas 1, 2, 3, 4 </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td>Dólares</td>
            <td>Colones</td>
        </tr>
        <tr>
            <td></td>
            <td>Mami Tiene</td>
            <td></td>
            <td>{{ $cabins['general']['generalPaidToDeyaniraUSD'] }}</td>
            <td>{{ $cabins['general']['generalPaidToDeyaniraCRC'] }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Limpiezas</td>
            <td></td>
            <td></td>
            <td>{{ $cabins['general']['generalCleaningCost'] }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Total Gastos</td>
            <td></td>
            <td></td>
            <td>{{ $cabins['general']['generalCost'] }}</td>
        </tr>
        <tr>
            <td></td>
            <td>50%</td>
            <td></td>
            <td>{{ $cabins['general']['general50PorcentUSD'] }}</td>
            <td>{{ $cabins['general']['general50PorcentCRC'] }}</td>
        </tr>
        <tr></tr>
        <tr>
            <td></td>
            <td>Total</td>
            <td></td>
            <td>{{ $cabins['general']['generalTotalUSD'] }}</td>
            <td>{{ $cabins['general']['generalTotalCRC'] }}</td>
        </tr>

    </tbody>
</table>
