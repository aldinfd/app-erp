@use('App\Models\SalesOrder')
@extends('reports.layout')

@section('title', 'Laporan Penjualan')
@section('period', 'Periode '.$report['from'].' s.d. '.$report['to'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Nomor Order</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="num">Subtotal</th>
                <th class="num">Pajak</th>
                <th class="num">Ongkir</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['orders'] as $order)
                <tr>
                    <td>{{ $order['order_number'] }}</td>
                    <td>{{ $order['order_date'] }}</td>
                    <td>{{ $order['customer_name'] }}</td>
                    <td>{{ SalesOrder::STATUS_LABELS[$order['status']] ?? $order['status'] }}</td>
                    <td class="num">{{ 'Rp '.number_format($order['subtotal'], 2, ',', '.') }}</td>
                    <td class="num">{{ 'Rp '.number_format($order['tax'], 2, ',', '.') }}</td>
                    <td class="num">{{ 'Rp '.number_format($order['shipping'], 2, ',', '.') }}</td>
                    <td class="num">{{ 'Rp '.number_format($order['grand_total'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada order pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">TOTAL {{ $report['total_orders'] }} order</td>
                <td class="num">{{ 'Rp '.number_format($report['total_subtotal'], 2, ',', '.') }}</td>
                <td class="num">{{ 'Rp '.number_format($report['total_tax'], 2, ',', '.') }}</td>
                <td class="num">{{ 'Rp '.number_format($report['total_shipping'], 2, ',', '.') }}</td>
                <td class="num">{{ 'Rp '.number_format($report['total_grand_total'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
