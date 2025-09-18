<?php
// app/Views/errors/500.php
http_response_code(500);
?><!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Hiba történt (500)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family:system-ui;max-width:720px;margin:40px auto;">
  <h1>Váratlan hiba (500)</h1>
  <p><?= htmlspecialchars($message ?? 'Ismeretlen hiba') ?></p>
  <p><a href="/unitarius/">Vissza a kezdőlapra</a></p>
</body>
</html>
