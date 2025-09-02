<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Giornate</title>
    <style>
 @page {
  margin-top: 5mm;
  margin-right: 8mm;
  margin-bottom: 5mm;
  margin-left: 8mm;
}


       @font-face {
        font-family: 'DejaVu Sans';
        src: url('https://cdnjs.cloudflare.com/ajax/libs/dejavu/2.37/dejavu-fonts-ttf-2.37/ttf/DejaVuSans.ttf') format('truetype');
    }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 40px;
            padding-top: 25mm;  /* <-- Aggiunto */
            padding-bottom: 25mm; /* <-- per il footer */        }
        h1 {
            font-size: 16pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo {
            height: 60px;
        }

    .header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 20mm;
   /* background-color: #c00;
    color: white; */
    text-align: left;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .header img {
    height: 10mm;
  }

        .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20mm;
    text-align: center;
    font-size: 10px;
   /* color: white;
   /* background-color: #c00;
   /* rosso Smart */
    padding-top: 5px;
  }
        .table-header {
            background-color: #d32f2f;
            color: white;
            font-weight: bold;
            border: 1px solid white;
        }

        .table-row td {
            border: 1px solid #d32f2f;
            padding: 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .section {
            margin-bottom: 20px;
        }


    </style>
</head>
<body>


<div class="header">
  <img src="{{ public_path('images/logo_placeholder.png') }}" alt="Logo">
  <h1 class="font-semibold text-lg mb-1"> <strong>Report Giornate</strong> Dal {{ \Carbon\Carbon::parse($dataInizio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dataFine)->format('d/m/Y') }} </h1>
</div>


    <div class="section">
        <strong>{{ $cliente->A_NomeVisualizzato ?? $cliente->CodCliente }}</strong><br>
        Cod. Fiscal: {{ $cliente->AG_CodiceFiscalePF ?? $cliente->CodCliente }}<br>
        Cell.: {{ $cliente->AD_Cellulare ?? $cliente->CodCliente }}<br>
        Email: {{ $cliente->AE_IndirizzoEmail ?? $cliente->CodCliente }}
    </div>

    <div class="section">
        <table>
            <thead>
                <tr class="table-header">
                    <td>Cod.Attività</td>
                    <td>TipoContratto</td>
                    <td>Data</td>
                    <td class="right">Retrib. lorda</td>
                    <td class="center">Diaria</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $totaleRetribuzione = 0;
                    $totaleDiaria = 0;
                @endphp

                @foreach ($giornate as $g)
                    <tr class="table-row">
                        <td>{{ $g->IDContratto }}</td>
                        <td>{{ $g->TipoContr }}</td>
                        <td>{{ \Carbon\Carbon::parse($g->Data)->format('d/m/Y') }}</td>
                        <td class="right">€ {{ number_format($g->Retribuzione ?? 0, 2, ',', '.') }}</td>
                        <td class="center">{{ ($g->DIARIA ?? false) ? 'SI' : 'NO' }}</td>
                    </tr>
                    @php
                        $totaleRetribuzione += floatval($g->Retribuzione);
                        if ($g->DIARIA) $totaleDiaria++;
                    @endphp
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <table>
            <tr>
                <td><strong>Totale giornate:</strong></td>
                <td class="right">{{ count($giornate) }}</td>
            </tr>
            <tr>
                <td><strong>Totale diarie:</strong></td>
                <td class="right">{{ $totaleDiaria }}</td>
            </tr>
            <tr>
                <td><strong>Totale retr. lorda:</strong></td>
                <td class="right">€ {{ number_format($totaleRetribuzione, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Smart soc. coop. impresa sociale<br>
        via Casoretto 41/A, Milano (MI) - 20131<br>
        CF e P. IVA IT08934230967<br>
        <a href="https://www.smart.coop" style="color: red;">www.smart.coop</a></p>
    </div>
</body>
</html>
