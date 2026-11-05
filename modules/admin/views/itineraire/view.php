<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */

$langues = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES'];
$this->title = $model->getTitle() ?: $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Itinéraires', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="itineraire-view">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?> <span class="texte-meta"><?= Html::encode($model->id) ?></span></h1>
            <div class="mt-2 d-flex flex-wrap gap-1">
                <?php foreach ($langues as $code => $label): ?>
                    <?= Html::a($label, ['view', 'id' => $model->id, 'lang' => $code], [
                        'class' => 'btn btn-sm ' . ($code === Yii::$app->language ? 'btn-primary' : 'btn-outline-secondary'),
                    ]) ?>
                <?php endforeach ?>
            </div>
        </div>
        <div class="page-header-actions">
            <?= Html::a(Icon::svg('pencil') . ' Modifier', ['update', 'id' => $model->id, 'lang' => Yii::$app->language], ['class' => 'btn btn-warning']) ?>
            <?= Html::a(Icon::svg('external-link') . ' PDF', Url::to(['/topoguide/pdf', 'lang' => Yii::$app->language, 'id' => $model->id]), ['class' => 'btn btn-outline-secondary', 'target' => '_blank']) ?>
            <?= Bouton::post(Icon::svg('refresh') . ' Générer carte', ['generer-carte', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Retour', ['index'], ['class' => 'btn btn-link']) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Identité</h2>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id', 'titre_2', 'raison_sociale', 'auteur', 'code_insee',
                        'commune_depart', 'commune_arrivee',
                        ['label' => 'Producteur', 'value' => $model->producteur ? $model->producteur->raison_sociale : '—'],
                    ],
                ]) ?>
            </section>
        </div>
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Caractéristiques</h2>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'distance_km', 'denivele', 'denivele_negatif_cumule', 'duree_txt',
                        ['label' => 'Durée (JSON)', 'value' => $model->getDureeVal()],
                        'latitude', 'longitude', 'balisage_couleur',
                        ['label' => 'Difficulté', 'value' => $model->getDifficulteVal()],
                        ['label' => 'Type', 'value' => $model->getTypeVal()],
                        ['label' => 'Boucle', 'value' => $model->getBoucleVal()],
                    ],
                ]) ?>
            </section>
        </div>
    </div>

    <div class="row g-4 mt-0">
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Textes</h2>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        ['label' => 'Descriptif', 'format' => 'ntext', 'attribute' => 'descriptif'],
                        ['label' => 'Descriptif mobile', 'format' => 'ntext', 'attribute' => 'descriptif_mobile'],
                        ['label' => 'Alerte info', 'format' => 'ntext', 'attribute' => 'alerte_info'],
                    ],
                ]) ?>
            </section>
        </div>
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Options &amp; Fichiers</h2>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        ['label' => 'Actif', 'value' => $model->is_active ? 'Oui' : 'Non'],
                        ['label' => 'Homologué FFR', 'value' => $model->homologue_ffr ? 'Oui' : 'Non'],
                        ['label' => 'Alerte', 'value' => $model->alerte ? 'Oui' : 'Non'],
                        'doc_gpx', 'doc_kml', 'video',
                        ['label' => 'Carte JPG', 'value' => $model->hasCarteCache() ? '✓ ' . $model->getCarteCacheDate() : '✗ absente'],
                    ],
                ]) ?>
            </section>
        </div>
    </div>

    <?php if ($model->getPhotos()): ?>
        <section class="panel mt-4">
            <h2 class="section-title">Photos (<?= count($model->getPhotos()) ?>)</h2>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <caption class="visually-hidden">Photos de l'itinéraire</caption>
                    <thead><tr><th scope="col">URL</th><th scope="col">Légende</th></tr></thead>
                    <tbody>
                    <?php foreach ($model->getPhotos() as $p): ?>
                        <tr>
                            <td><a href="<?= Html::encode(Yii::$app->params['mediaCdnUrl'] . '/' . ($p['url'] ?? '')) ?>" target="_blank"><?= Html::encode($p['url'] ?? '') ?></a></td>
                            <td><?= Html::encode($p['legende'] ?? '') ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>

    <?php if ($model->getEtapes()): ?>
        <section class="panel mt-4">
            <h2 class="section-title">Étapes (<?= count($model->getEtapes()) ?>)</h2>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <caption class="visually-hidden">Étapes de l'itinéraire</caption>
                    <thead><tr><th scope="col">Nom</th><th scope="col">Lat</th><th scope="col">Lon</th></tr></thead>
                    <tbody>
                    <?php foreach ($model->getEtapes() as $e): ?>
                        <tr>
                            <td><?= Html::encode($e['nom'] ?? '') ?></td>
                            <td><?= Html::encode($e['lat'] ?? '') ?></td>
                            <td><?= Html::encode($e['lon'] ?? '') ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>
</div>
