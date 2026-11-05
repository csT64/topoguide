<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\Itineraire $model */
/** @var string|null $carteUrl */

$etapes = $model->getEtapes();

$etapesCoords = [];
foreach ($etapes as $e) {
    $lat = preg_replace('/[^\d.\-]/', '', $e['latitudedecimalegooglemap'] ?? $e['lat'] ?? '');
    $lon = preg_replace('/[^\d.\-]/', '', $e['longitudedecimalegooglemap'] ?? $e['lon'] ?? '');
    $etapesCoords[] = ['nom' => $e['nom_etape'] ?? $e['nom'] ?? '—', 'lat' => $lat, 'lon' => $lon];
}

$this->title = 'Carte — ' . ($model->getTitle() ?: $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Itinéraires', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->getTitle() ?: $model->id, 'url' => ['view', 'id' => $model->id, 'lang' => 'fr']];
$this->params['breadcrumbs'][] = 'Carte';
?>
<div class="itineraire-carte">
    <div class="page-header mb-3">
        <div>
            <h1>Éditeur de carte <span class="texte-meta"><?= Html::encode($model->id) ?></span></h1>
            <p class="page-header-sub"><?= Html::encode($model->getTitle()) ?></p>
        </div>
        <div class="page-header-actions">
            <?= Html::a('← Retour liste', ['index'], ['class' => 'btn btn-link']) ?>
            <?= Html::a('Voir fiche', ['view', 'id' => $model->id, 'lang' => 'fr'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <section class="panel">
                <h2 class="section-title"><?= Icon::svg('chart-bar') ?> Carte actuelle</h2>
                <?php if ($carteUrl): ?>
                    <img src="<?= Html::encode($carteUrl) ?>" style="max-width:100%;border:1px solid var(--color-border);border-radius:4px" alt="Carte de l'itinéraire">
                    <div class="mt-2 d-flex gap-2">
                        <?= Bouton::post(Icon::svg('trash') . ' Supprimer', ['supprimer-carte', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-danger', 'data-confirm' => 'Supprimer cette carte ?']) ?>
                        <?= Bouton::post(Icon::svg('refresh') . ' Régénérer', ['generer-carte', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-warning']) ?>
                    </div>
                <?php else: ?>
                    <p class="text-body-secondary"><em>Aucune carte générée.</em></p>
                    <?= Bouton::post(Icon::svg('refresh') . ' Générer maintenant', ['generer-carte', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
                <?php endif ?>
            </section>

            <section class="panel mt-3">
                <h2 class="section-title"><?= Icon::svg('map-pin') ?> Tracé</h2>
                <div class="small">
                    <?php if ($model->doc_gpx): ?>
                        <p class="mb-1"><strong>GPX :</strong> <a href="<?= Html::encode($model->doc_gpx) ?>" target="_blank"><?= Html::encode(basename($model->doc_gpx)) ?></a></p>
                    <?php endif ?>
                    <?php if ($model->doc_kml): ?>
                        <p class="mb-1"><strong>KML :</strong> <a href="<?= Html::encode($model->doc_kml) ?>" target="_blank"><?= Html::encode(basename($model->doc_kml)) ?></a></p>
                    <?php endif ?>
                    <?php if (!$model->doc_gpx && !$model->doc_kml): ?>
                        <p class="mb-1 text-body-secondary"><em>Pas de tracé — carte centrée sur le départ.</em></p>
                    <?php endif ?>
                    <div class="hint-block mt-2">Les fichiers GPX/KML sont fournis par TourInSoft et ne sont pas modifiables ici. Seule la position des marqueurs est éditable.</div>
                </div>
            </section>
        </div>

        <div class="col-md-7">
            <section class="panel">
                <h2 class="section-title"><?= Icon::svg('map-pin') ?> Positionnement des marqueurs <span class="text-body-secondary fw-normal small">— glissez pour repositionner</span></h2>
                <div id="leaflet-map" style="height:380px;border-radius:4px"></div>
                <p class="texte-meta mt-2 mb-0">Cliquez sur un marqueur pour voir ses coordonnées. Glissez-le pour le repositionner, puis sauvegardez.</p>
            </section>
        </div>
    </div>

    <!-- Formulaire d'édition des positions -->
    <?= Html::beginForm(['sauvegarder-positions', 'id' => $model->id], 'post') ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

    <section class="panel mt-4">
        <h2 class="section-title">Positions des marqueurs</h2>

        <h3 class="mb-2">Départ</h3>
        <div class="row g-3 mb-3">
            <div class="col-sm-4">
                <label class="form-label" for="lat_depart">Latitude</label>
                <input type="text" id="lat_depart" name="lat_depart" class="form-control form-control-sm"
                       value="<?= Html::encode($model->latitude) ?>" placeholder="ex: 43.1481746">
            </div>
            <div class="col-sm-4">
                <label class="form-label" for="lon_depart">Longitude</label>
                <input type="text" id="lon_depart" name="lon_depart" class="form-control form-control-sm"
                       value="<?= Html::encode($model->longitude) ?>" placeholder="ex: -0.4557289">
            </div>
            <div class="col-sm-4 d-flex align-items-end">
                <span class="texte-meta">Coordonnées du point de départ</span>
            </div>
        </div>

        <?php if ($etapesCoords): ?>
            <h3 class="mb-2">Étapes</h3>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-3">
                    <caption class="visually-hidden">Coordonnées des étapes</caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width:40px">#</th>
                            <th scope="col">Nom</th>
                            <th scope="col" style="width:160px">Latitude</th>
                            <th scope="col" style="width:160px">Longitude</th>
                            <th scope="col" style="width:60px">Carte</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etapesCoords as $i => $e): ?>
                            <tr>
                                <td class="text-center"><span class="badge text-bg-danger"><?= $i + 1 ?></span></td>
                                <td><?= Html::encode($e['nom']) ?></td>
                                <td><input type="text" id="etape_lat_<?= $i ?>" name="etape_lat[<?= $i ?>]" class="form-control form-control-sm etape-lat" data-index="<?= $i ?>" value="<?= Html::encode($e['lat']) ?>"></td>
                                <td><input type="text" id="etape_lon_<?= $i ?>" name="etape_lon[<?= $i ?>]" class="form-control form-control-sm etape-lon" data-index="<?= $i ?>" value="<?= Html::encode($e['lon']) ?>"></td>
                                <td class="text-center"><?= ($e['lat'] && $e['lon']) ? '<span class="pill pill-ok"><span class="pill-dot"></span></span>' : '<span class="pill pill-danger"><span class="pill-dot"></span></span>' ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-body-secondary"><em>Aucune étape avec coordonnées dans ce JSON.</em></p>
        <?php endif ?>

        <div class="d-flex align-items-center gap-3">
            <?= Html::submitButton(Icon::svg('device-floppy') . ' Sauvegarder et régénérer la carte', ['class' => 'btn btn-primary']) ?>
            <span class="texte-meta">La carte sera automatiquement régénérée après sauvegarde.</span>
        </div>
    </section>

    <?= Html::endForm() ?>
</div>

<link rel="stylesheet" href="/gmap/leaflet_min.css">
<script src="/gmap/leaflet.js"></script>
<script src="/gmap/leaflet.gpx.js"></script>
<script src="/gmap/leaflet.kml.js"></script>
<script>
(function() {
    var latD   = parseFloat(document.getElementById('lat_depart').value) || 43.3;
    var lonD   = parseFloat(document.getElementById('lon_depart').value) || -0.37;
    var etapes = <?= json_encode(array_values($etapesCoords)) ?>;
    var gpxUrl = <?= json_encode($model->doc_gpx ? Yii::$app->urlManager->createUrl(['/gmap/proxy', 'url' => $model->doc_gpx]) : null) ?>;
    var kmlUrl = <?= json_encode($model->doc_kml ? Yii::$app->urlManager->createUrl(['/gmap/proxy', 'url' => $model->doc_kml]) : null) ?>;

    var map = L.map('leaflet-map').setView([latD, lonD], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 18
    }).addTo(map);

    if (gpxUrl) {
        new L.GPX(gpxUrl, {
            async: true,
            polyline_options: { color: '#0f3296', weight: 3, opacity: 0.9 },
            marker_options: { startIconUrl: null, endIconUrl: null, shadowUrl: null, wptIconUrls: { '': null } }
        }).on('loaded', function(e) { map.fitBounds(e.target.getBounds(), { padding: [40, 40] }); })
          .on('error', function() { console.warn('GPX non chargé'); }).addTo(map);
    }
    if (!gpxUrl && kmlUrl) {
        fetch(kmlUrl).then(function(r) { return r.text(); }).then(function(kml) {
            var layer = new L.KML(kml);
            map.addLayer(layer);
            map.fitBounds(layer.getBounds(), { padding: [40, 40] });
        }).catch(function() { console.warn('KML non chargé'); });
    }

    function pinIcon(color, label) {
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38">'
            + '<path d="M14 0C6.3 0 0 6.3 0 14c0 10 14 24 14 24s14-14 14-24C28 6.3 21.7 0 14 0z" fill="' + color + '"/>'
            + '<circle cx="14" cy="14" r="8" fill="white" opacity="0.9"/>'
            + '<text x="14" y="19" text-anchor="middle" font-family="Arial,sans-serif" font-weight="bold" font-size="11" fill="' + color + '">' + label + '</text></svg>';
        return L.divIcon({ html: svg, iconSize: [28, 38], iconAnchor: [14, 38], popupAnchor: [0, -38], className: '' });
    }

    var markerD = L.marker([latD, lonD], { draggable: true, icon: pinIcon('#2d882d', 'D') }).addTo(map).bindPopup('<strong>Départ</strong>');
    markerD.on('dragend', function(e) {
        var ll = e.target.getLatLng();
        document.getElementById('lat_depart').value = ll.lat.toFixed(7);
        document.getElementById('lon_depart').value = ll.lng.toFixed(7);
    });
    document.getElementById('lat_depart').addEventListener('change', function() {
        var la = parseFloat(document.getElementById('lat_depart').value);
        var lo = parseFloat(document.getElementById('lon_depart').value);
        if (!isNaN(la) && !isNaN(lo)) markerD.setLatLng([la, lo]);
    });

    var etapeMarkers = [];
    etapes.forEach(function(e, i) {
        var la = parseFloat(e.lat), lo = parseFloat(e.lon);
        if (isNaN(la) || isNaN(lo)) return;
        var m = L.marker([la, lo], { draggable: true, icon: pinIcon('#d22323', String(i + 1)) }).addTo(map).bindPopup('<strong>Étape ' + (i + 1) + '</strong><br>' + e.nom);
        m.on('dragend', function(ev) {
            var ll = ev.target.getLatLng();
            document.getElementById('etape_lat_' + i).value = ll.lat.toFixed(7);
            document.getElementById('etape_lon_' + i).value = ll.lng.toFixed(7);
        });
        etapeMarkers.push(m);
    });

    document.querySelectorAll('.etape-lat, .etape-lon').forEach(function(inp) {
        inp.addEventListener('change', function() {
            var idx = parseInt(this.dataset.index);
            var la = parseFloat(document.getElementById('etape_lat_' + idx).value);
            var lo = parseFloat(document.getElementById('etape_lon_' + idx).value);
            if (!isNaN(la) && !isNaN(lo) && etapeMarkers[idx]) etapeMarkers[idx].setLatLng([la, lo]);
        });
    });

    var allLatLng = [[latD, lonD]];
    etapes.forEach(function(e) {
        var la = parseFloat(e.lat), lo = parseFloat(e.lon);
        if (!isNaN(la) && !isNaN(lo)) allLatLng.push([la, lo]);
    });
    if (allLatLng.length > 1) map.fitBounds(allLatLng, { padding: [30, 30] });
})();
</script>
