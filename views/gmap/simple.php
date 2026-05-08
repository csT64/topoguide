<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/gmap/leaflet_min.css" />
<script src="/gmap/leaflet.js"></script>
<style>
  html, body, #map { width: 100%; height: 100%; margin: 0; padding: 0; }
</style>
</head>
<body>
<div id="map"></div>
<script>
var map = L.map('map').setView([<?= (float)$lat ?>, <?= (float)$lon ?>], <?= (int)$zoom ?>);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(map);
L.marker([<?= (float)$lat ?>, <?= (float)$lon ?>]).addTo(map);
<?php foreach ($marker ?? [] as $m):
    $parts = explode('!', $m);
    if (count($parts) >= 4): ?>
L.marker([<?= (float)$parts[2] ?>, <?= (float)$parts[3] ?>]).bindTooltip(<?= json_encode($parts[0]) ?>).addTo(map);
<?php endif; endforeach; ?>
</script>
</body>
</html>
