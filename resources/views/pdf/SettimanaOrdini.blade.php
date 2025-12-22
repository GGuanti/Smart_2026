<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Settimana S{{ str_pad($week, 2, '0', STR_PAD_LEFT) }} - {{ $year }}</title>

    <style>
        @page { margin: 18mm 12mm 18mm 12mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header { margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: 700; }
        .sub { font-size: 11px; color: #4b5563; margin-top: 2px; }

        /* ✅ Riepilogo */
        .summary {
            margin: 10px 0 12px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            font-size: 13px;
            font-weight: 700;
        }

        /* ✅ Ordine = sezione unita + NO SPLIT tra pagine */
        .order {
            border: 1px solid #111;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;

            /* 🔥 Anti page-break dentro la card */
            page-break-inside: avoid !important;
            break-inside: avoid !important;

            /* dompdf spesso aiuta anche così */
            display: block;
        }

        /* ✅ Ancora più robusto: evita split anche sui figli */
        .order * {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .order-header {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .order-meta {
            font-size: 10px;
            color: #374151;
            margin-bottom: 6px;
        }

        .muted { color: #6b7280; }

        /* ✅ Riga prodotto + check a lato (dompdf: meglio flex che table) */
        .product-row {
            display: block;
            width: 100%;
            margin-top: 2px;
        }

        .product-left {
            display: inline-block;
            width: 48%;
            vertical-align: middle;
            white-space: nowrap;
        }

        .product-right {
            display: inline-block;
            width: 100%;
            vertical-align: middle;
            white-space: nowrap;
        }

        .h2 { font-weight: 700; }

        .checks { white-space: nowrap; }

        /* ✅ Check senza grassetto + box grande */
        .check {
            display: inline-block;
            margin-right: 14px;
            font-weight: normal;
            font-size: 11px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .check .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 2px solid #111;
            margin-right: 8px;
            vertical-align: middle;
            box-sizing: border-box;
        }

        .check.checked .box { background: #111; }

        .notes { margin-top: 10px; }
        .line {
            border-bottom: 1px solid #cbd5e1;
            height: 14px;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">
            SETTIMANA {{ str_pad($week, 2, '0', STR_PAD_LEFT) }} — {{ $year }}
        </div>
        <div class="sub">
            Dal {{ $start->format('d/m/Y') }} al {{ $end->format('d/m/Y') }}
            — <b>Stampato il:</b> {{ \Carbon\Carbon::parse($printedAt)->format('d/m/Y H:i') }}
        </div>
    </div>

    @php
        // label prodotto
        $labelProd = function($code) {
            return match($code) {
                'IA' => 'Infissi Alluminio',
                'PA' => 'Persiane',
                'SC' => 'Scuroni',
                'CA' => 'Cover Alluminio',
                default => $code ?: 'Prodotto',
            };
        };

        // helper check box (HTML)
        $check = function(bool $val, string $text) {
            $cls = $val ? 'check checked' : 'check';
            return '<span class="'.$cls.'"><span class="box"></span>'.$text.'</span>';
        };

        // ✅ Totale pezzi settimana (sempre definito)
        $totPezziSettimana = 0;
        if (!empty($orders)) {
            foreach ($orders as $o) {
                $totPezziSettimana += (int) (($o['head']->Pezzi ?? 0));
            }
        }
    @endphp

    <div class="summary">
        Totale pezzi settimana: {{ $totPezziSettimana }}
    </div>

    @if($orders->isEmpty())
        <div class="muted">Nessun ordine in questa settimana.</div>
    @else

        @foreach($orders as $o)
            @php
                $a = $o['head'];
                $items = $o['items'];

                if (in_array($a->StatoMagazzino, ['Magazzino', 'Arrivato'])) {
                    $magazzinoLabel = 'Merce disponibile';
                } elseif ($a->StatoMagazzino === 'Ordinato') {
                    $magazzinoLabel = 'In arrivo';
                } else {
                    $magazzinoLabel = $a->StatoMagazzino; // In ritardo
                }
            @endphp

            <div class="order">
                {{-- ✅ Testata unificata --}}
                <div class="order-header">
                    N° {{ $a->Nordine }} — {{ $a->title }} — {{ \Carbon\Carbon::parse($a->DataInizio)->format('d/m/Y') }}
                    @if(!empty($a->Riferimento))
                        — Rif.: {{ $a->Riferimento }}
                    @endif
                </div>

                <div class="order-meta">
                    Stato: {{ $a->status }}
                    | Magazzino: {{ $magazzinoLabel }}
                    | Tot. pezzi: {{ $a->Pezzi ?? 0 }}
                </div>

                {{-- ✅ Righe prodotto (dentro la stessa sezione ordine) --}}
                @foreach($items as $it)
                    @php
                        $prod = $it->Prodotto ?? null;

                        $taglio = (bool)($it->Taglio ?? false);
                        $assemblaggio = (bool)($it->Assemblaggio ?? false);
                        $comandi = (bool)($it->Comandi ?? false);

                        $taglio_zoccolo = (bool)($it->TaglioZoccolo ?? false);
                        $taglio_lamelle = (bool)($it->TaglioLamelle ?? false);
                        $montaggio_lamelle = (bool)($it->MontaggioLamelle ?? false);

                        $ferramenta = (bool)($it->Ferramenta ?? false);
                        $vetratura = (bool)($it->Vetratura ?? false);
                    @endphp

                    <div class="product-row h2">
                        <div class="product-left">
                            {{ $labelProd($prod) }}
                            <span class="muted">
                                — Colore: {{ $it->Colore ?? '-' }}
                                — Pezzi: {{ $it->Pezzi ?? 0 }}
                            </span>
                        </div>

                        <div class="product-right">
                            <div class="checks">
                                @if($prod === 'IA')
                                    {!! $check($taglio, 'Taglio') !!}
                                    {!! $check($assemblaggio, 'Assemblaggio') !!}
                                    {!! $check($ferramenta, 'Ferramenta') !!}
                                    {!! $check($vetratura, 'Vetratura') !!}

                                @elseif($prod === 'PA')
                                    {!! $check($taglio, 'Taglio') !!}
                                    {!! $check($taglio_zoccolo, 'Taglio Zoccolo') !!}
                                    {!! $check($taglio_lamelle, 'Taglio Lamelle') !!}
                                    {!! $check($assemblaggio, 'Assemblaggio') !!}
                                    {!! $check($comandi, 'Montaggio Comandi') !!}
                                    {!! $check($montaggio_lamelle, 'Montaggio Lamelle') !!}

                                @elseif($prod === 'SC' || $prod === 'CA')
                                    {!! $check($taglio, 'Taglio') !!}
                                    {!! $check($assemblaggio, 'Assemblaggio') !!}

                                @else
                                    {!! $check($taglio, 'Taglio') !!}
                                    {!! $check($assemblaggio, 'Assemblaggio') !!}
                                    {!! $check($ferramenta, 'Ferramenta') !!}
                                    {!! $check($vetratura, 'Vetratura') !!}
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(!empty($it->Descrizione))
                        <div class="muted"><b>Descrizione:</b> {{ $it->Descrizione }}</div>
                    @endif
                @endforeach

                <div class="notes">
                    <div class="muted"><b>Note:</b></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>
            </div>
        @endforeach
    @endif

    {{-- ✅ Footer: Data stampa + Numero pagina su TUTTE le pagine --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 9;

            $y = $pdf->get_height() - 28;
            $x_left = 36;
            $x_right = $pdf->get_width() - 160;

            $dateText = "Stampato il: {{ \Carbon\Carbon::parse($printedAt)->format('d/m/Y H:i') }}";
            $pdf->page_text($x_left, $y, $dateText, $font, $size, array(107,114,128));

            $pageText = "Pagina {PAGE_NUM} / {PAGE_COUNT}";
            $pdf->page_text($x_right, $y, $pageText, $font, $size, array(107,114,128));
        }
    </script>
</body>
</html>
