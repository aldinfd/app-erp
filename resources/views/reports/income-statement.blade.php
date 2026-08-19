@extends('reports.layout')

@section('title', 'Laporan Laba Rugi')
@section('period', 'Periode '.$report['from'].' s.d. '.$report['to'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Akun</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3"><strong>Pendapatan</strong></td>
            </tr>
            @forelse ($report['revenues'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="num">{{ 'Rp '.number_format($row['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="3"><strong>Beban</strong></td>
            </tr>
            @forelse ($report['expenses'] as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="num">{{ 'Rp '.number_format($row['amount'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Pendapatan</td>
                <td class="num">{{ 'Rp '.number_format($report['total_revenue'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Total Beban</td>
                <td class="num">{{ 'Rp '.number_format($report['total_expense'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">{{ $report['net_income'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</td>
                <td class="num">{{ 'Rp '.number_format(abs($report['net_income']), 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
