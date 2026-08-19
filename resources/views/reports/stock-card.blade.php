@use('App\Models\StockMovement')
@extends('reports.layout')

@section('title', 'Kartu Stok — '.$card['product']['sku'].' '.$card['product']['name'])
@section('period', 'Periode '.$card['from'].' s.d. '.$card['to'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th class="num">Qty ({{ $card['product']['unit'] }})</th>
                <th class="num">Saldo ({{ $card['product']['unit'] }})</th>
                <th>Referensi</th>
                <th>Catatan</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3">Saldo awal</td>
                <td class="num">{{ number_format($card['opening'], 2, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
            @forelse ($card['lines'] as $line)
                <tr>
                    <td>{{ $line['date'] }}</td>
                    <td>{{ StockMovement::TYPE_LABELS[$line['type']] ?? $line['type'] }}</td>
                    <td class="num">{{ number_format($line['qty'], 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($line['balance'], 2, ',', '.') }}</td>
                    <td>{{ $line['reference'] ?? '-' }}</td>
                    <td>{{ $line['note'] ?? '-' }}</td>
                    <td>{{ $line['user'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada mutasi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Saldo akhir</td>
                <td class="num">{{ number_format($card['closing'], 2, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <p>Total masuk: {{ number_format($card['total_in'], 2, ',', '.') }} {{ $card['product']['unit'] }}
       &mdash; Total keluar: {{ number_format(abs($card['total_out']), 2, ',', '.') }} {{ $card['product']['unit'] }}</p>
@endsection
