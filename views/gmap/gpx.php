<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="/gmap/leaflet_min.css" />
<script src="/gmap/leaflet.js"></script>
<script src="/gmap/leaflet.gpx.js"></script>
<style>
  html, body, #map { width: 100%; height: 100%; margin: 0; padding: 0; }
</style>
</head>
<body>
<div id="map"></div>
<script>
var map = L.map('map');
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(map);

new L.GPX(<?= json_encode($file) ?>, {
    async: true,
    marker_options: { startIconUrl: '/gmap/pin-icon-start.png', endIconUrl: '/gmap/pin-icon-end.png', shadowUrl: '' }
}).on('loaded', function(e) {
    map.fitBounds(e.target.getBounds());
}).addTo(map);

<?php foreach ($marker ?? [] as $m):
    $parts = explode('!', $m);
    if (count($parts) >= 4): ?>
L.marker([<?= (float)$parts[2] ?>, <?= (float)$parts[3] ?>]).bindTooltip(<?= json_encode($parts[0]) ?>).addTo(map);
<?php endif; endforeach; ?>
</script>
</body>
</html>
