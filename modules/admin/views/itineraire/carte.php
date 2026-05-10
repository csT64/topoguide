<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\Itineraire $model */
/** @var string|null $carteUrl */

$etapes = $model->getEtapes();

// Extraction des coordonnées d'étapes
$etapesCoords = [];
foreach ($etapes as $e) {
    $lat = preg_replace('/[^\d.\-]/', '', $e['latitudedecimalegooglemap']  ?? $e['lat'] ?? '');
    $lon = preg_replace('/[^\d.\-]/', '', $e['longitudedecimalegooglemap'] ?? $e['lon'] ?? '');
    $etapesCoords[] = [
        'nom' => $e['nom_etape'] ?? $e['nom'] ?? '—',
        'lat' => $lat,
        'lon' => $lon,
    ];
}

$this->title = 'Carte — ' . ($model->getTitle() ?: $model->id);
?>

<h2>
    Éditeur de carte
    <small><?= Html::encode($model->id) ?> — <?= Html::encode($model->getTitle()) ?></small>
</h2>

<div style="margin-bottom:12px">
    <?= Html::a('← Retour liste', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
    <?= Html::a('Voir fiche', ['view', 'id' => $model->id, 'lang' => 'fr'], ['class' => 'btn btn-default btn-sm']) ?>
</div>

<div class="row">

  <!-- Carte JPG actuelle -->
  <div class="col-md-5">
    <div class="panel panel-default">
      <div class="panel-heading"><strong>Carte actuelle</strong></div>
      <div class="panel-body" style="text-align:center;padding:8px">
        <?php if ($carteUrl): ?>
          <img src="<?= Html::encode($carteUrl) ?>" style="max-width:100%;border:1px solid #ddd" alt="Carte">
          <div style="margin-top:8px">
            <?= Html::a('Supprimer la carte', ['supprimer-carte', 'id' => $model->id], [
                'class'       => 'btn btn-xs btn-danger',
                'data-method' => 'post',
                'data-confirm' => 'Supprimer cette carte ?',
            ]) ?>
            <?= Html::a('Régénérer', ['generer-carte', 'id' => $model->id], [
                'class'       => 'btn btn-xs btn-warning',
                'data-method' => 'post',
            ]) ?>
          </div>
        <?php else: ?>
          <p class="text-muted"><em>Aucune carte générée.</em></p>
          <?= Html::a('Générer maintenant', ['generer-carte', 'id' => $model->id], [
              'class'       => 'btn btn-warning btn-sm',
              'data-method' => 'post',
          ]) ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Info tracé GPX/KML -->
    <div class="panel panel-info">
      <div class="panel-heading"><strong>Tracé</strong></div>
      <div class="panel-body" style="font-size:12px">
        <?php if ($model->doc_gpx): ?>
          <strong>GPX :</strong> <a href="<?= Html::encode($model->doc_gpx) ?>" target="_blank"><?= Html::encode(basename($model->doc_gpx)) ?></a><br>
        <?php endif; ?>
        <?php if ($model->doc_kml): ?>
          <strong>KML :</strong> <a href="<?= Html::encode($model->doc_kml) ?>" target="_blank"><?= Html::encode(basename($model->doc_kml)) ?></a><br>
        <?php endif; ?>
        <?php if (!$model->doc_gpx && !$model->doc_kml): ?>
          <em>Pas de tracé GPX/KML — carte centrée sur le départ.</em>
        <?php endif; ?>
        <div class="alert alert-info" style="margin-top:8px;padding:6px 10px;font-size:11px;margin-bottom:0">
          ℹ️ Les fichiers GPX/KML sont fournis par TourInSoft et ne sont pas modifiables ici. Seule la position des <strong>marqueurs</strong> est éditable.
        </div>
      </div>
    </div>
  </div>

  <!-- Carte Leaflet interactive -->
  <div class="col-md-7">
    <div class="panel panel-default">
      <div class="panel-heading"><strong>Positionnement des marqueurs</strong> <small>— glissez pour repositionner</small></div>
      <div class="panel-body" style="padding:0">
        <div id="leaflet-map" style="height:380px"></div>
      </div>
      <div class="panel-footer" style="font-size:11px;color:#777">
        Cliquez sur un marqueur pour voir ses coordonnées. Glissez-le pour le repositionner, puis sauvegardez.
      </div>
    </div>
  </div>
</div>

<!-- Formulaire d'édition des positions -->
<?php $form = \yii\widgets\ActiveForm::begin(['action' => ['sauvegarder-positions', 'id' => $model->id], 'method' => 'post']); ?>

<div class="panel panel-warning">
  <div class="panel-heading"><strong>Positions des marqueurs</strong></div>
  <div class="panel-body">

    <!-- Départ -->
    <h4 style="margin-top:0">
      <span class="label" style="background:#2d882d;font-size:13px">D</span>
      Départ
    </h4>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label>Latitude</label>
          <input type="text" id="lat_depart" name="lat_depart" class="form-control input-sm"
                 value="<?= Html::encode($model->latitude) ?>"
                 placeholder="ex: 43.1481746">
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label>Longitude</label>
          <input type="text" id="lon_depart" name="lon_depart" class="form-control input-sm"
                 value="<?= Html::encode($model->longitude) ?>"
                 placeholder="ex: -0.4557289">
        </div>
      </div>
      <div class="col-sm-4" style="padding-top:23px">
        <small class="text-muted">Coordonnées du point de départ de l'itinéraire</small>
      </div>
    </div>

    <!-- Étapes -->
    <?php if ($etapesCoords): ?>
    <hr>
    <h4><span class="label label-danger" style="font-size:13px">1–<?= count($etapesCoords) ?></span> Étapes</h4>
    <table class="table table-condensed table-bordered" style="margin-bottom:0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Nom</th>
          <th style="width:160px">Latitude</th>
          <th style="width:160px">Longitude</th>
          <th style="width:80px">Sur carte</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($etapesCoords as $i => $e): ?>
        <tr>
          <td style="text-align:center">
            <span class="label label-danger"><?= $i + 1 ?></span>
          </td>
          <td><?= Html::encode($e['nom']) ?></td>
          <td>
            <input type="text"
                   id="etape_lat_<?= $i ?>"
                   name="etape_lat[<?= $i ?>]"
                   class="form-control input-sm etape-lat"
                   data-index="<?= $i ?>"
                   value="<?= Html::encode($e['lat']) ?>">
          </td>
          <td>
            <input type="text"
                   id="etape_lon_<?= $i ?>"
                   name="etape_lon[<?= $i ?>]"
                   class="form-control input-sm etape-lon"
                   data-index="<?= $i ?>"
                   value="<?= Html::encode($e['lon']) ?>">
          </td>
          <td style="text-align:center">
            <?php if ($e['lat'] && $e['lon']): ?>
              <span class="text-success">✓</span>
            <?php else: ?>
              <span class="text-danger">✗</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p class="text-muted"><em>Aucune étape avec coordonnées dans ce JSON.</em></p>
    <?php endif; ?>
  </div>
  <div class="panel-footer">
    <button type="submit" class="btn btn-success">
      <span class="glyphicon glyphicon-floppy-disk"></span> Sauvegarder et régénérer la carte
    </button>
    <small class="text-muted" style="margin-left:12px">La carte sera automatiquement régénérée après sauvegarde.</small>
  </div>
</div>

<?php \yii\widgets\ActiveForm::end(); ?>

<link rel="stylesheet" href="/gmap/leaflet_min.css">
<script src="/gmap/leaflet.js"></script>
<script>
(function() {
    var latD  = parseFloat(document.getElementById('lat_depart').value) || 43.3;
    var lonD  = parseFloat(document.getElementById('lon_depart').value) || -0.37;
    var etapes = <?= json_encode(array_values($etapesCoords)) ?>;

    var map = L.map('leaflet-map').setView([latD, lonD], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18
    }).addTo(map);

    // Icône épingle départ (vert)
    function pinIcon(color, label) {
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 28 38">'
            + '<path d="M14 0C6.3 0 0 6.3 0 14c0 10 14 24 14 24s14-14 14-24C28 6.3 21.7 0 14 0z" fill="' + color + '"/>'
            + '<circle cx="14" cy="14" r="8" fill="white" opacity="0.9"/>'
            + '<text x="14" y="19" text-anchor="middle" font-family="Arial,sans-serif" font-weight="bold" font-size="11" fill="' + color + '">' + label + '</text>'
            + '</svg>';
        return L.divIcon({
            html: svg,
            iconSize: [28, 38],
            iconAnchor: [14, 38],
            popupAnchor: [0, -38],
            className: ''
        });
    }

    // Marqueur départ
    var markerD = L.marker([latD, lonD], {
        draggable: true,
        icon: pinIcon('#2d882d', 'D')
    }).addTo(map).bindPopup('<strong>Départ</strong>');

    markerD.on('dragend', function(e) {
        var ll = e.target.getLatLng();
        document.getElementById('lat_depart').value = ll.lat.toFixed(7);
        document.getElementById('lon_depart').value = ll.lng.toFixed(7);
    });

    document.getElementById('lat_depart').addEventListener('change', updateDepartMarker);
    document.getElementById('lon_depart').addEventListener('change', updateDepartMarker);
    function updateDepartMarker() {
        var la = parseFloat(document.getElementById('lat_depart').value);
        var lo = parseFloat(document.getElementById('lon_depart').value);
        if (!isNaN(la) && !isNaN(lo)) markerD.setLatLng([la, lo]);
    }

    // Marqueurs étapes
    var etapeMarkers = [];
    etapes.forEach(function(e, i) {
        var la = parseFloat(e.lat);
        var lo = parseFloat(e.lon);
        if (isNaN(la) || isNaN(lo)) return;
        var m = L.marker([la, lo], {
            draggable: true,
            icon: pinIcon('#d22323', String(i + 1))
        }).addTo(map).bindPopup('<strong>Étape ' + (i + 1) + '</strong><br>' + e.nom);

        m.on('dragend', function(ev) {
            var ll = ev.target.getLatLng();
            document.getElementById('etape_lat_' + i).value = ll.lat.toFixed(7);
            document.getElementById('etape_lon_' + i).value = ll.lng.toFixed(7);
        });
        etapeMarkers.push(m);
    });

    // Synchronisation champs → marqueur
    document.querySelectorAll('.etape-lat, .etape-lon').forEach(function(inp) {
        inp.addEventListener('change', function() {
            var idx = parseInt(this.dataset.index);
            var la  = parseFloat(document.getElementById('etape_lat_' + idx).value);
            var lo  = parseFloat(document.getElementById('etape_lon_' + idx).value);
            if (!isNaN(la) && !isNaN(lo) && etapeMarkers[idx]) {
                etapeMarkers[idx].setLatLng([la, lo]);
            }
        });
    });

    // Ajuster la vue pour inclure tous les marqueurs
    var allLatLng = [[latD, lonD]];
    etapes.forEach(function(e) {
        var la = parseFloat(e.lat), lo = parseFloat(e.lon);
        if (!isNaN(la) && !isNaN(lo)) allLatLng.push([la, lo]);
    });
    if (allLatLng.length > 1) {
        map.fitBounds(allLatLng, {padding: [30, 30]});
    }
})();
</script>
