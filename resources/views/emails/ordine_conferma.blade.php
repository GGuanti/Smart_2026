<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Report Giornate</title>
</head>

<body style="background-color: #ffffff; font-family: Arial, sans-serif; padding: 20px;">

    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border: 1px solid #ddd;">
        <tr>
            <td style="padding: 20px;">
                <h2 style="color: #333;">📄 Conferma Ordine Cliente {{ $ordine->CognomeNome }}</h2>
                <p style="color:#555;">
                    In allegato trovi la conferma ordine n.
                    <strong>{{ $ordine->Nordine }}</strong>
                    del
                    <strong>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong>.
                </p>
                <p style="color: #777;">
                    Grazie,<br>
                    {{ $ordine->CognomeNome }}<br><br>
                </p>
                <img src="{{ $message->embed(public_path('Foto/logo.png')) }}" alt="Logo" style="max-height:10mm; max-height:38px; height:auto; width:auto; display:block;">

            </td>
        </tr>
    </table>

</body>

</html>
