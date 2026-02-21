{{-- resources/views/Reports/ordini/conferma_isomax.blade.php --}}
<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <title>Conferma Ordine</title>

    <style>
        @page {
            margin: 22mm 12mm 18mm 12mm;
        }

        /* Base */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.25;
        }

        p {
            margin: 1.4mm 0;
        }

        /* Header / footer (Dompdf fixed) */
        header {
            position: fixed;
            top: -14mm;
            left: 0;
            right: 0;
            height: 12mm;
        }

        footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            height: 10mm;
        }

        .hrow {
            width: 100%;
            border-collapse: collapse;
        }

        .hrow td {
            padding: 0;
            vertical-align: top;
        }

        .h-left {
            text-align: left;
        }

        .h-right {
            text-align: right;
            white-space: nowrap;
        }

        .page:before {
            content: counter(page);
        }

        .pages:before {
            content: counter(pages);
        }

        .footline {
            font-size: 10px;
            text-align: center;
            white-space: nowrap;
            color: #222;
        }

        /* Top content */
        .intro {
            margin-top: 2mm;
            padding: 2.5mm 3mm;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            background: #f7f7f7;
        }

        .title-line {
            margin-top: 2mm;
            font-weight: bold;
        }

        /* Modern “card” for each item */
        .card {
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            margin: 4mm 0;
            page-break-inside: avoid;
        }

        .card-head {
            padding: 2mm 3mm;
            border-bottom: 1px solid #e3e3e3;
            background: #f2f2f2;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .card-head table {
            width: 100%;
            border-collapse: collapse;
        }

        .card-head .left {
            font-weight: bold;
            font-size: 11.5px;
        }

        .card-head .right {
            text-align: right;
            color: #111;
            font-weight: bold;
        }

        .card-body {
            width: 100%;
            border-collapse: collapse;
        }

        .card-left {
            width: 40mm;
            vertical-align: top;
            padding: 3mm;
            border-right: 1px solid #eeeeee;
        }

        .card-right {
            vertical-align: top;
            padding: 3mm 4mm;
        }

        .imgbox {
            border: 1px solid #e6e6e6;
            border-radius: 6px;
            padding: 2mm;
            text-align: center;
            background: #fff;
        }

        .imgbox img {
            width: 30mm;
            height: auto;
        }

        .badge {
            display: inline-block;
            margin-top: 2mm;
            padding: 1mm 2mm;
            border: 1px solid #d9d9d9;
            border-radius: 999px;
            background: #f7f7f7;
            font-weight: bold;
            font-size: 10px;
        }

        .page-break {
            page-break-before: always;
        }

        .last-page {
            page-break-inside: avoid;
        }


        .kv {
            margin: 0.9mm 0;
        }

        .k {
            font-weight: bold;
        }

        .muted {
            color: #444;
        }

        .divider {
            margin: 2mm 0;
            border-top: 1px dashed #d9d9d9;
        }

        /* --- Last page modern --- */
        .last-wrap {
            margin-top: 6mm;
        }

        .panel {
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #fafafa;
            padding: 3mm 4mm;
        }

        .panel-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 2mm;
        }

        .kv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1mm;
        }

        .kv-table td {
            padding: 1.2mm 0;
            vertical-align: top;
        }

        .kv-table .k {
            width: 42mm;
            font-weight: bold;
            color: #222;
        }

        .kv-table .v {
            color: #111;
        }

        .sep {
            border-top: 1px dashed #cfcfcf;
            margin: 3mm 0;
        }

        .sign-title {
            font-weight: bold;
            margin: 2mm 0 1mm;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10mm;
        }

        .sign-table td {
            text-align: center;
            padding: 2mm 0;
        }

        .sign-line {
            border-top: 1px solid #000;
            margin: 0 auto;
        }

        .total-box {
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #fff;
            padding: 3mm 4mm;
        }

        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }

        .total-table td {
            padding: 1.4mm 0;
        }

        .total-table .lab {
            color: #222;
        }

        .total-table .val {
            text-align: right;
            font-weight: bold;
        }

        .total-table .thin {
            font-weight: normal;
        }

        .total-table .grand td {
            border-top: 1px solid #000;
            padding-top: 2mm;
        }

        .total-table .grand .lab,
        .total-table .grand .val {
            font-size: 12px;
            font-weight: bold;
        }

        /* ===== Last page layout like screenshot ===== */
        .last-page-wrap {
            margin-top: 10mm;
        }

        .lp-row {
            width: 100%;
            border-collapse: collapse;
        }

        .lp-row td {
            vertical-align: top;
        }

        .summary-card {
            width: 62mm;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #fff;
            padding: 4mm 4mm 3mm;
        }

        .summary-title {
            font-weight: bold;
            font-size: 12px;
            margin: 0 0 3mm 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .summary-table td {
            padding: 1.4mm 0;
        }

        .summary-table .lab {
            color: #222;
        }

        .summary-table .val {
            text-align: right;
            font-weight: bold;
        }

        .summary-table .sep td {
            border-top: 1px solid #000;
            padding-top: 2mm;
        }

        .cond-card {
            margin-top: 10mm;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            background: #f7f7f7;
            padding: 5mm 6mm;
        }

        .cond-title {
            font-weight: bold;
            font-size: 12px;
            margin: 0 0 4mm 0;
        }

        .cond-kv {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .cond-kv td {
            padding: 1.4mm 0;
        }

        .cond-kv .k {
            width: 45mm;
            font-weight: bold;
            color: #222;
        }

        .cond-kv .v {
            color: #111;
        }

        .dashed {
            border-top: 1px dashed #cfcfcf;
            margin: 4mm 0;
        }

        .accept {
            font-weight: bold;
            margin-top: 2mm;
        }

        .sign {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12mm;
        }

        .sign td {
            text-align: center;
            padding: 2mm 0;
        }

        .sign-line {
            border-top: 1px solid #000;
            width: 80mm;
            margin: 0 auto;
        }

        .sign-line-small {
            border-top: 1px solid #000;
            width: 55mm;
            margin: 0 auto;
        }

        .summary-box {
            border: 1px solid #d9d9d9;
            border-radius: 12px;
            background: #fff;
            padding: 5mm 6mm;
        }

        .summary-title {
            font-weight: 800;
            font-size: 13px;
            margin: 0 0 4mm 0;
            text-align: center;
            color: #111;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11.5px;
        }

        .summary-table col:first-child {
            width: 62%;
        }

        .summary-table col:last-child {
            width: 38%;
        }

        .summary-table td {
            padding: 1.6mm 0;
            vertical-align: top;
        }

        .summary-table .lab {
            color: #222;
        }

        .summary-table .val {
            text-align: right;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .summary-table .muted {
            color: #555;
            font-weight: 400;
        }

        .summary-table .sep td {
            border-top: 1px dashed #cfcfcf;
            padding-top: 2.5mm;
        }

        .summary-table .grand td {
            border-top: 1px solid #000;
            padding-top: 2.5mm;
        }

        .summary-table .grand .lab {
            font-weight: 900;
            font-size: 13px;
        }

        .summary-table .grand .val {
            font-weight: 900;
            font-size: 13px;
        }
    </style>
</head>

<body>
    @php
    $dataDoc = \Illuminate\Support\Carbon::parse($ordine->DataOrdine)->format('d/m/Y');
    $nOrd = $ordine->Nordine;
    @endphp
    @php
    // --- Date / N° ordine ---
    $dataDoc = \Illuminate\Support\Carbon::parse($ordine->DataOrdine)->format('d/m/Y');
    $nOrd = $ordine->Nordine ?? '';

    // --- Totale righe (IVA esclusa) ---
    $imponibile = collect($righe)->sum(function ($x) {
    $qta = (float)($x->Qta ?? 0);
    $cad = (float)($x->PrezzoCad ?? 0);
    $man = (float)($x->PrezzoMan ?? 0);
    return $qta * ($cad + $man);
    });

    // --- Sconti (singoli) ---
    $s1 = (float)($ordine->Sconto1 ?? 0);
    $s2 = (float)($ordine->Sconto2 ?? 0);

    // --- Sconto totale COMPOSTO (%)
    $a = max(0, min(100, $s1)) / 100;
    $b = max(0, min(100, $s2)) / 100;
    $scontoTotPerc = (1 - (1 - $a) * (1 - $b)) * 100;

    // --- Imponibile scontato (applico i due sconti in cascata)
    $imponibileScontato = $imponibile;
    if ($s1 > 0) $imponibileScontato *= (1 - $s1 / 100);
    if ($s2 > 0) $imponibileScontato *= (1 - $s2 / 100);

    // --- Importo sconto in euro
    $scontoEuro = $imponibile - $imponibileScontato;

    // --- IVA (se NULL -> 22%)
    // se non hai un campo IVA nel model, lascia 22 fisso
    $ivaPerc = (float)($ordine->IvaPerc ?? 22);

    // --- IVA e totale ivato
    $iva = $imponibileScontato * ($ivaPerc / 100);
    $totaleIvato = $imponibileScontato + $iva;
    @endphp

    <header>
        <table class="hrow">
            <tr>
                <td class="h-left">
                    <img src="{{ public_path('Logo.png') }}" style="height:25mm">
                </td>
                <td class="h-right">
                    <div class="muted">Data Stampa: {{ $dataDoc }}</div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <div class="footline">
            Isomax s.r.l. - Zona Industriale - 85050 TITO (PZ) - P.IVA 01111840763
        </div>
        <div class="footline">
            Tel. +39 0971 485220 - info@isomaxporte.com
        </div>
    </footer>

    <main>

        <div class="intro">
            <div class="title-line">
                {{ $ordine->TipoDoc }} N. {{ $nOrd }} – Utente: {{ $utente ?? '' }}
            </div>

            <p>
                <strong>Spett.le</strong> {{ $ordine->CognomeNome }}<br>
                {{ $ordine->CAP }} {{ $ordine->IdCitta }}<br>
                P.IVA: {{ $ordine->PIva }}<br>
                Tel: {{ $ordine->Telefono }} – Cell: {{ $ordine->Cellulare }}
            </p>

            <p class="muted">
                Con la presente Vi sottoponiamo la nostra migliore offerta come di seguito elencato.
            </p>
        </div>

        {{-- ================== RIGHE ORDINE ================== --}}
        @foreach($righe as $r)
        @php
        // Path reali per file_exists + Dompdf (Windows safe)
        $imgAbs = 'Foto/' . $r->nome_modello . '.jpg';
        $fallbackAbs = 'Foto/default.jpg';

        $imgSrc = str_replace('\\','/',$imgAbs);
        $fallbackSrc = str_replace('\\','/',$fallbackAbs);



        // Verso (non hai ancora tabella aperture agganciata: metto placeholder)

        @endphp

        <div class="card">

            {{-- HEAD --}}
            <div class="card-head">
                <table>
                    <tr>
                        <td class="left">
                            {{ $r->TipoSoluzione ?? 'Soluzione' }}
                        </td>
                        <td class="right">
                            L={{ $r->DimL }} • A={{ $r->DimA }} • Sp. Muro={{ $r->DimSp }} • Pz.={{ $r->Qta }} •  Tot. Riga: €.  {{ (float)$r->Qta * (float)$r->PrezzoCad + (float)$r->PrezzoMan }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- BODY --}}
            <table class="card-body">
                <tr>
                    <td class="card-left">
                        <div class="imgbox">
                            @if(!empty($r->nome_modello) && file_exists($imgAbs))
                            <img src="{{ $imgSrc }}" alt="{{ $r->nome_modello }}" style="width:14mm;">
                            @elseif(file_exists($fallbackAbs))
                            <img src="{{ $fallbackSrc }}" alt="Prodotto" style="width:14mm;">
                            @endif
                        </div>

                        <div class="badge">Verso: {{ $r->Verso }}</div>
                    </td>

                    <td class="card-right">
                        <div class="kv">
                            <span class="k">Modello:</span>
                            {{ $r->nome_modello }}
                            @if(!empty($r->ColoreAnta))
                            <span class="muted"> • </span>
                            <span class="k">Colore Anta:</span> {{ $r->ColoreAnta }}
                            @endif
                        </div>

                        @if(!empty($r->ColoreTelaio))
                        <div class="kv">
                            <span class="k">Colore Telaio:</span> {{ $r->ColoreTelaio }}
                        </div>
                        @endif

                        @if(!empty($r->TipoTelaio))
                        <div class="kv">
                            <span class="k">Mostrine:</span> {{ $r->TipoTelaio }}
                        </div>
                        @endif
                        @if(!empty($r->Vetro))
                        <div class="kv">
                            <span class="k">Vetro:</span> {{ $r->Vetro }}
                        </div>
                        @endif



                        @if(!empty($r->Serratura))
                        <div class="kv">
                            <span class="k">Serratura:</span> {{ $r->Serratura }}
                        </div>
                        @endif

                        @if(!empty($r->Cerniere))
                        <div class="kv">
                            <span class="k">Cerniere:</span> {{ $r->Cerniere }}
                        </div>
                        @endif

                        @if(!empty($r->NoteMan))
                        <div class="divider"></div>
                        <div class="kv">
                            <span class="k">Note:</span> {{ $r->NoteMan }}
                        </div>
                        @endif
                    </td>
                </tr>
            </table>

        </div>
        @endforeach
        <div class="page-break"></div>
        <div class="last-page last-page-wrap">

            {{-- RIEPILOGO in alto (centrato) --}}
            <div style="margin-top:5mm;">
                <div style="margin-top:5mm;">
                    <div class="summary-box">


                        <table class="summary-table">
                            <colgroup>
                                <col>
                                <col>
                            </colgroup>

                            <tr>
                                <td class="lab"><strong>Totale ordine</strong></td>
                                <td class="val"><strong>{{ number_format($imponibile, 2, ',', '.') }} €</strong></td>
                            </tr>

                            <tr class="sep">
                                <td class="lab">Sconto applicato</td>
                                <td class="val">
                                    {{ number_format($scontoTotPerc, 2, ',', '.') }}%
                                    <span class="muted">
                                        ({{ number_format($s1, 2, ',', '.') }}% + {{ number_format($s2, 2, ',', '.') }}%)
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="lab">Sconto in €</td>
                                <td class="val">{{ number_format($scontoEuro, 2, ',', '.') }} €</td>
                            </tr>

                            <tr class="sep">
                                <td class="lab">Totale IVA esclusa</td>
                                <td class="val">{{ number_format($imponibileScontato, 2, ',', '.') }} €</td>
                            </tr>

                            <tr>
                                <td class="lab">IVA {{ number_format($ivaPerc, 0) }}%</td>
                                <td class="val">{{ number_format($iva, 2, ',', '.') }} €</td>
                            </tr>

                            <td class="lab"><strong>Totale ordine</strong></td>
                                <td class="val"><strong>{{ number_format($imponibile, 2, ',', '.') }} €</strong></td>

                            <tr class="grand">
                                <td class="lab"><strong>Totale Complessivo</strong></td>
                                <td class="val"><strong>{{ number_format($totaleIvato, 2, ',', '.') }} €</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>



            {{-- CONDIZIONI sotto (card grande) --}}
            <div class="cond-card">
                <div class="cond-title">Condizioni Generali di vendita</div>

                <table class="cond-kv">
                    <tr>
                        <td class="k">Consegna Richiesta</td>
                        <td class="v">{{ $ordine->ConsegnaRichiesta ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Trasporto</td>
                        <td class="v">{{ $ordine->Trasporto ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Validità Offerta</td>
                        <td class="v">{{ $ordine->ValiditaOfferta ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Pagamento</td>
                        <td class="v">{{ $ordine->Pagamento ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Annotazioni</td>
                        <td class="v">{{ $ordine->Annotazioni ?? '' }}</td>
                    </tr>
                </table>

                <div class="dashed"></div>

                <div class="accept">
                    Per accettazione di quanto sopra indicato in tutte le pagine dell'ordine:
                </div>

                <table class="sign">
                    <tr>
                        <td style="width:35%;">Data</td>
                        <td style="width:65%;">Firma del committente</td>
                    </tr>
                    <tr>
                        <td style="padding-top:16mm;">
                            <div class="sign-line-small"></div>
                        </td>
                        <td style="padding-top:16mm;">
                            <div class="sign-line"></div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>


    </main>
    @php
    $nOrdinePdf = $ordine->Nordine ?? '';
    @endphp
    <script type="text/php">
        if (isset($pdf)) {
    $font = $fontMetrics->get_font("DejaVu Sans", "normal");
    $size = 9;


    $ordine = "{{ $nOrdinePdf }}";
    $pdf->page_text(
        430, 35,
        "Pag. {PAGE_NUM} di {PAGE_COUNT} – Ordine N. {$ordine}",
        $font,
        $size,
        [0,0,0]
    );
}
</script>

</body>

</html>
