<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $stats */

$this->title = 'Cache PDF / HTML';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cache-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Bouton::post(
                Icon::svg('alert-triangle') . ' Purger inactives',
                ['purge-inactive'],
                ['class' => 'btn btn-warning', 'data-confirm' => 'Supprimer le cache de toutes les fiches inactives ou supprimées ?']
            ) ?>
            <?= Bouton::post(
                Icon::svg('trash') . ' Vider tout',
                ['purge-all'],
                ['class' => 'btn btn-danger', 'data-confirm' => 'Vider l\'intégralité du cache ? Les PDF et HTML seront régénérés à la prochaine demande.']
            ) ?>
        </div>
    </div>

    <h2 class="visually-hidden">Statistiques par langue</h2>
    <div class="stat-cards mb-4">
        <?php foreach ($stats as $lang => $counts): ?>
            <div class="stat-card">
                <div class="stat-card-icon"><?= Icon::svg('database', ['class' => 'ic-lg']) ?></div>
                <h3><?= strtoupper(Html::encode($lang)) ?></h3>
                <p class="kpi"><?= $counts['pdf'] + $counts['html'] ?></p>
                <p class="stat-card-sub mb-0"><?= $counts['pdf'] ?> PDF · <?= $counts['html'] ?> HTML</p>
            </div>
        <?php endforeach ?>
    </div>

    <section class="panel mb-4">
        <h2 class="section-title"><?= Icon::svg('files') ?> Purger une fiche spécifique</h2>
        <?= Html::beginForm(Url::to(['purge-fiche']), 'post', ['class' => 'row g-2 align-items-end']) ?>
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
            <div class="col-md-6">
                <label class="form-label visually-hidden" for="syndicId">syndic_object_id</label>
                <?= Html::input('text', 'syndicId', '', [
                    'id'          => 'syndicId',
                    'class'       => 'form-control',
                    'placeholder' => 'syndic_object_id  (ex : ITIAQU064V5C6JJI)',
                ]) ?>
            </div>
            <div class="col-auto">
                <?= Html::submitButton('Purger', ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        <?= Html::endForm() ?>
    </section>

    <section class="panel">
        <h2 class="section-title"><?= Icon::svg('info-circle') ?> Cycle de vie des fiches</h2>
        <p>Deux vérifications <strong>indépendantes</strong> sont effectuées à chaque requête :</p>
        <ol>
            <li><code>is_active = 0</code> <strong>ou</strong> <code>deleted_at IS NOT NULL</code> → HTTP 404 + purge du cache (quel que soit <code>updated_at</code>).</li>
            <li><code>updated_at</code> postérieur à <code>generated_at</code> (meta fichier) → régénération (indépendamment de <code>is_active</code>).</li>
        </ol>
        <p class="text-body-secondary mb-0">
            <code>updated_at</code> n'est <strong>pas</strong> mis à jour lors d'une dépublication — seulement lors d'un changement de contenu ou d'une republication.
        </p>
    </section>
</div>
