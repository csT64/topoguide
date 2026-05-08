<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */

$langues = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES'];
$this->title = $model->getTitle() ?: $model->id;
?>
<h2>
  <?= Html::encode($this->title) ?>
  <small><?= Html::encode($model->id) ?></small>
</h2>

<div style="margin-bottom:15px">
  <?php foreach ($langues as $code => $label):
    $current = Yii::$app->language;
    $class = $code === $current ? 'btn btn-primary btn-xs' : 'btn btn-default btn-xs';
    echo Html::a($label, ['view', 'id' => $model->id, 'lang' => $code], ['class' => $class]) . ' ';
  endforeach; ?>

  <?= Html::a('Modifier', ['update', 'id' => $model->id, 'lang' => Yii::$app->language], ['class' => 'btn btn-warning btn-sm']) ?>
  <?= Html::a('PDF ↗', Url::to(['/topoguide/pdf', 'lang' => Yii::$app->language, 'id' => $model->id]), ['class' => 'btn btn-info btn-sm', 'target' => '_blank']) ?>
  <?= Html::a('Générer carte', ['generer-carte', 'id' => $model->id], ['class' => 'btn btn-default btn-sm', 'data-method' => 'post']) ?>
  <?= Html::a('Retour', ['index'], ['class' => 'btn btn-link btn-sm']) ?>
</div>

<div class="row">
  <div class="col-md-6">
    <h4>Identité</h4>
    <?= DetailView::widget([
      'model' => $model,
      'attributes' => [
        'id',
        'titre_2',
        'raison_sociale',
        'auteur',
        'code_insee',
        'commune_depart',
        'commune_arrivee',
        [
          'label' => 'Producteur',
          'value' => $model->producteur ? $model->producteur->raison_sociale : '—',
        ],
      ],
    ]) ?>
  </div>
  <div class="col-md-6">
    <h4>Caractéristiques</h4>
    <?= DetailView::widget([
      'model' => $model,
      'attributes' => [
        'distance_km',
        'denivele',
        'denivele_negatif_cumule',
        'duree_txt',
        [
          'label' => 'Durée (JSON)',
          'value' => $model->getDureeVal(),
        ],
        'latitude',
        'longitude',
        'balisage_couleur',
        [
          'label' => 'Difficulté',
          'value' => $model->getDifficulteVal(),
        ],
        [
          'label' => 'Type',
          'value' => $model->getTypeVal(),
        ],
        [
          'label' => 'Boucle',
          'value' => $model->getBoucleVal(),
        ],
      ],
    ]) ?>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <h4>Textes</h4>
    <?= DetailView::widget([
      'model' => $model,
      'attributes' => [
        ['label' => 'Descriptif', 'format' => 'ntext', 'attribute' => 'descriptif'],
        ['label' => 'Descriptif mobile', 'format' => 'ntext', 'attribute' => 'descriptif_mobile'],
        ['label' => 'Alerte info', 'format' => 'ntext', 'attribute' => 'alerte_info'],
      ],
    ]) ?>
  </div>
  <div class="col-md-6">
    <h4>Options &amp; Fichiers</h4>
    <?= DetailView::widget([
      'model' => $model,
      'attributes' => [
        ['label' => 'Actif', 'value' => $model->is_active ? 'Oui' : 'Non'],
        ['label' => 'Homologué FFR', 'value' => $model->homologue_ffr ? 'Oui' : 'Non'],
        ['label' => 'Alerte', 'value' => $model->alerte ? 'Oui' : 'Non'],
        'doc_gpx',
        'doc_kml',
        'video',
        [
          'label' => 'Carte JPG',
          'value' => $model->hasCarteCache() ? '✓ ' . $model->getCarteCacheDate() : '✗ absente',
        ],
      ],
    ]) ?>
  </div>
</div>

<?php if ($model->getPhotos()): ?>
<h4>Photos (<?= count($model->getPhotos()) ?>)</h4>
<table class="table table-condensed table-bordered">
  <thead><tr><th>URL</th><th>Légende</th></tr></thead>
  <tbody>
  <?php foreach ($model->getPhotos() as $p): ?>
    <tr>
      <td><a href="<?= Html::encode(Yii::$app->params['mediaCdnUrl'] . '/' . ($p['url'] ?? '')) ?>" target="_blank"><?= Html::encode($p['url'] ?? '') ?></a></td>
      <td><?= Html::encode($p['legende'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if ($model->getEtapes()): ?>
<h4>Étapes (<?= count($model->getEtapes()) ?>)</h4>
<table class="table table-condensed table-bordered">
  <thead><tr><th>Nom</th><th>Lat</th><th>Lon</th></tr></thead>
  <tbody>
  <?php foreach ($model->getEtapes() as $e): ?>
    <tr>
      <td><?= Html::encode($e['nom'] ?? '') ?></td>
      <td><?= Html::encode($e['lat'] ?? '') ?></td>
      <td><?= Html::encode($e['lon'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
