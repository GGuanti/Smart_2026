<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Report Preventivo</title>
</head>

<body style="background-color: #ffffff; font-family: Arial, sans-serif; padding: 20px;">
       @php
   $userImg = public_path('Foto/Utente/' . auth()->id() . '.png');

    @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; border: 1px solid #ddd;">
        <tr>
            <td style="padding: 20px;">
                <h2 style="color: #333;">📄 Preventivo  {{ $ordine->CognomeNome }}</h2>
                <p style="color:#555;">
                    In allegato trovi la Preventivo N.
                    <strong>{{ $ordine->Nordine }}</strong>
                    del
                    <strong>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong>.
                </p>
                <p style="color: #777;">
<p style="color:#777; margin-bottom:6px;">
    Grazie,<br>
    <strong>{{ $utente }}</strong>
</p>
                </p>
<img src="{{ $message->embed($userImg) }}"
     width="130"
     style="width:130px; height:auto; display:block;">
            </td>
        </tr>
    </table>

</body>

</html>
