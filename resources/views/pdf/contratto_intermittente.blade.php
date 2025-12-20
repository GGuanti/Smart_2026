<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Contratto di Lavoro Intermittente</title>

    <style>
        /* Margini pagina (spazio per header e footer) */
        @page {
            margin: 110px 40px 60px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            line-height: 1.0;
        }

        /* HEADER FISSO — appare su ogni pagina */
        .header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 90px;
        }

        .header img {
            width: 120px;
        }

        /* FOOTER FISSO (opzionale) */
        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 10pt;
            color: #666;
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        .justify { text-align: justify; }
        .center { text-align: center; }
        .mt-10 { margin-top: 10px; }
        .mt-20 { margin-top: 20px; }
        .mt-40 { margin-top: 40px; }
    </style>
</head>

<body>

    <!-- HEADER SU OGNI PAGINA -->
    <div class="header" style="display:flex; justify-content:space-between; align-items:center;">

    <!-- LOGO A SINISTRA -->
    <img src="{{ public_path('images/logo.png') }}" alt="Logo Smart" style="width:120px;">
    </div>

    <h2>Contratto di Lavoro Intermittente            N: <strong>{{ $NUM_CONTRATTO }}</strong></h2>

    <p class="center" style="color:#e00000;">
        Tra
    </p>

    <p class="justify">
        <strong>{{ $NOME_COMPLETO }}</strong>,
        nato/a a {{ strtoupper($LUOGO_NASCITA) }}
        @if(!empty($PROV_NASCITA)) ({{ strtoupper($PROV_NASCITA) }}) @endif
        il {{ $DATA_NASCITA }},
        residente in {{ strtoupper($INDIRIZZO) }} — {{ $CAP }} —
        {{ strtoupper($COMUNE_RES) }}
        @if(!empty($PROV_RES)) ({{ strtoupper($PROV_RES) }}) @endif,
        {{ strtoupper($STATO_RES) }},
        codice fiscale {{ strtoupper($COD_FISCALE) }},
        d’ora in avanti “il/la Dipendente”.
    </p>

    <p class="center mt-20" style="color:#e00000;">e</p>

    <p class="justify">
        <strong>Smart Società Cooperativa Impresa Sociale</strong>, CF/P.IVA 08394320967,
        con sede in Via Casoretto 41/A, Milano (20131),
        rappresentata dal sig. Donato Nubile,
        d’ora in avanti “il Datore di Lavoro”.
    </p>

    <p class="justify mt-10">
        Congiuntamente: “le Parti”.
    </p>

    <p class="justify mt-20" style="color:#e00000;">Premesso che</p>
    <p class="justify mt-10">
        a) Il Datore di Lavoro intende ricorrere al lavoro intermittente secondo gli artt. 13 e ss.
        del D.Lgs. 81/2015 e secondo quanto previsto dal D.M. 23/10/2004.
        Il CCNL applicato è: <strong>{{ $CCNL }}</strong>.
    </p>

    <p class="justify mt-10">
        b) Il/La Dipendente accetta le condizioni del presente contratto intermittente.
    </p>

    <p class="mt-20" style="color:#e00000;">Le parti convengono che:</p>
    <p class="mt-20" style="color:#e00000;">1. Premesse</p>
    <p class="justify">
        Le premesse sono parte integrante del contratto.
    </p>

    <p class="mt-10"  style="color:#e00000;">2. Oggetto, mansioni e inquadramento</p>
    <p class="justify">
        2.1) Il/La Dipendente è assunto/a con contratto intermittente dal
        <strong>{{ $DATA_INIZIO }}</strong> al <strong>{{ $DATA_FINE }}</strong>.<br><br>

        2.2) La prestazione lavorativa avviene a chiamata, secondo gli artt. 13–18 del D.Lgs. 81/2015.<br><br>

        2.3) Mansione: <strong>{{ $PROFESSIONE }}</strong> — Livello: <strong>{{ $LIVELLO }}</strong>.
    </p>

    <p class="mt-10"  style="color:#e00000;">3. Patto di prova</p>
    <p class="justify">
        Il rapporto è soggetto a periodo di prova secondo CCNL, durante il quale è risolvibile senza preavviso.
    </p>

    <p class="mt-10"  style="color:#e00000;">4. Luogo di lavoro</p>
    <p class="justify">
        La sede ordinaria di lavoro è la residenza comunicata. Il/La Dipendente accetta eventuali spostamenti per le attività.
    </p>

    <p class="mt-10"  style="color:#e00000;">5. Trattamento economico</p>
    <p class="justify">
        5.1) Il compenso relativo alle giornate lavorate è corrisposto entro il giorno 10 del mese successivo.<br><br>

        5.2) Eventuali accordi economici integrativi hanno validità solo per la singola chiamata.<br><br>

        5.3) La retribuzione diretta e differita (escluso TFR) matura solo per i giorni effettivamente lavorati.<br><br>

        5.4) Nei periodi senza chiamata non matura alcun compenso.
    </p>

    <p class="mt-10"  style="color:#e00000;">6. Orario di lavoro</p>
    <p class="justify">
        La durata della prestazione è comunicata con la chiamata.
    </p>

    <p class="mt-10"  style="color:#e00000;">7. Modalità della chiamata</p>
    <p class="justify">
        La chiamata è effettuata con almeno 24 ore di preavviso, via telefono o email.
        Il lavoratore deve comunicare eventuali variazioni dei recapiti.
    </p>


    <p class="mt-10" </p>
    <span style="color:#e00000;">8. Impedimenti alla chiamata</span>
    <span style="color:#000000;"></span>

    <p class="justify">
        In caso di malattia o indisponibilità il lavoratore deve comunicare tempestivamente e fornire certificazione.
    </p>

    <p class="mt-10" style="color:#e00000;">9. Privacy</p>
    <p class="justify">
        Il Datore tratta i dati del Dipendente secondo GDPR UE 2016/679.
    </p>

    <p class="mt-10"  style="color:#e00000;">10. Sicurezza</p>
    <p class="justify">
        Il Datore applica il D.Lgs. 81/2008. Il Dipendente si impegna a rispettare tutte le norme di sicurezza.
    </p>

    <p class="mt-10"  style="color:#e00000;">11. Riservatezza</p>
    <p class="justify">
        Il Dipendente mantiene riservati dati e informazioni conosciute durante il rapporto.
    </p>

    <p class="mt-10"  style="color:#e00000;">12. Rinvio al CCNL</p>
    <p class="justify">
        Per quanto non previsto valgono le norme del CCNL.
    </p>

    <p class="justify mt-20">
        Si richiede la restituzione del presente contratto firmato.
    </p>

    <p class="justify mt-20">
        {{ $LUOGO_CONTRATTO }}, <strong>{{ $DATA_CONTRATTO }}</strong>
    </p>

    <table width="100%" class="mt-40">
        <tr>

        <td class="center">
            <strong>Il Datore di Lavoro</strong><br>
            <!-- Firma immagine -->
            <img src="{{ public_path('images/firma.png') }}"
                style="width:180px; margin-top:10px;"><br>
            ________________________
    </td>
            <td class="center">
            <strong>Il/La Dipendente </strong> <br><br><br><br>
                ________________________
            </td>
        </tr>
    </table>

    <script type="text/php">
    if (isset($pdf)) {

        $font = $fontMetrics->get_font("DejaVu Sans", "normal");

        // Testo numerazione
        $text = "Pagina {PAGE_NUM} di {PAGE_COUNT}";

        // Dimensioni pagina
        $w = $pdf->get_width();
        $h = $pdf->get_height();

        // POSIZIONE: in basso a destra
        $x = $w - 140;
        $y = $h - 45;

        // Dimensione font
        $size = 9;

        // ⚫ NERO — questo colore è quello VERO usato da Dompdf
        $color = array(0, 0, 0);  // RGB nero

        // Scrivi testo NERO su tutte le pagine
        $pdf->page_text($x, $y, $text, $font, $size, $color);
    }
</script>
</body>

</html>
