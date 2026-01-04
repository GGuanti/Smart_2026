<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h2>Conferma Ordine #{{ $ordine->Nordine ?? $ordine->ID }}</h2>
    <p>Cliente: {{ $ordine->CognomeNome }}</p>
    <p>Email: {{ $ordine->Email }}</p>
</body>
</html>
