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
        <h2 style="color: #333;">📄 Report Giornate</h2>
        <p style="color: #555;">
          In allegato trovi il report richiesto per il cliente <strong>{{ $codCliente }} {{ $clienteCognome }} {{ $clienteNome }}</strong>.
        </p>

        <p>
          <a href="{{ url("/report/giornate/preview?codCliente={$codCliente}&dataInizio={$dataInizio}&dataFine={$dataFine}") }}"
             style="display: inline-block; background-color: #4CAF50; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
            📥 Apri Report Online
          </a>
        </p>
        <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo" style="width:150px;">
        <p style="color: #777;">
    Grazie,<br>
    {{ $utenteNome }}
</p>

      </td>
    </tr>
  </table>

</body>
</html>

