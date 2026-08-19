@extends('reports.layout')

@section('title', 'Neraca')
@section('period', 'Posisi keuangan per '.$report['as_of'])

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
                <td colspan="3"><strong>Aset</strong></td>
            </tr>
            @forelse ($report['assets'] as $row)
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
                <td colspan="3"><strong>Liabilitas</strong></td>
            </tr>
            @forelse ($report['liabilities'] as $row)
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
                <td colspan="3"><strong>Ekuitas</strong></td>
            </tr>
            @forelse ($report['equity'] as $row)
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
                <td></td>
                <td>Laba Tahun Berjalan</td>
                <td class="num">{{ 'Rp '.number_format($report['current_earnings'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Aset</td>
                <td class="num">{{ 'Rp '.number_format($report['total_assets'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Total Liabilitas</td>
                <td class="num">{{ 'Rp '.number_format($report['total_liabilities'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2">Total Ekuitas (termasuk laba tahun berjalan)</td>
                <td class="num">{{ 'Rp '.number_format($report['total_equity'] + $report['current_earnings'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
