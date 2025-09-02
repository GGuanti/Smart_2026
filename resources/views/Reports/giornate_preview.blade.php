@extends('layouts.app')

@section('title', 'Anteprima Giornate')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Anteprima Giornate</h2>
        <a href="{{ url()->previous() }}" class="text-blue-600 hover:underline">← Indietro</a>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <h4 class="font-semibold text-lg mb-1">Intestatario</h4>
            <p><strong>Nome:</strong> {{ $intestatario['Nome'] }}</p>
            <p><strong>Cod. Fiscale:</strong> {{ $intestatario['CodFiscale'] }}</p>
            <p><strong>Email:</strong> {{ $intestatario['Email'] }}</p>
            <p><strong>Cell:</strong> {{ $intestatario['Cell'] }}</p>
        </div>
        <div>
            <h4 class="font-semibold text-lg mb-1">Contratto</h4>
            <p><strong>Tipo:</strong> {{ $intestatario['TipoContratto'] }}</p>
            <p><strong>Inizio:</strong> {{ $intestatario['DataInizio'] }}</p>
            <p><strong>Fine:</strong> {{ $intestatario['DataFine'] }}</p>
            <p><strong>Committente:</strong> {{ $committente }}</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full border">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Data</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Retribuzione</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Diaria</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totaleRetribuzione = 0;
                    $totaleDiaria = 0;
                @endphp
                @foreach ($giornate as $g)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($g->Data)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right">€ {{ number_format($g->Retribuzione, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-center">{{ $g->DIARIA ? 'Sì' : 'No' }}</td>
                    </tr>
                    @php
                        $totaleRetribuzione += $g->Retribuzione ?? 0;
                        if ($g->DIARIA) $totaleDiaria++;
                    @endphp
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100">
                <tr>
                    <td class="px-4 py-2 font-bold">Totali</td>
                    <td class="px-4 py-2 text-right font-bold">€ {{ number_format($totaleRetribuzione, 2, ',', '.') }}</td>
                    <td class="px-4 py-2 text-center font-bold">{{ $totaleDiaria }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
