<?php

use app\helpers\Icon;
use app\widgets\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\ItineraireSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Itinéraires';
$this->params['breadcrumbs'][] = $this->title;

$langues = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES'];
$current = $searchModel->langue;
?>
<div class="itineraire-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?php foreach ($langues as $code => $label): ?>
                <?= Html::a($label, Url::current(['ItineraireSearch[langue]' => $code]), [
                    'class' => 'btn btn-sm ' . ($code === $current ? 'btn-primary' : 'btn-outline-secondary'),
                ]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= GridView::widget([
        'caption'      => 'Liste des itinéraires',
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns' => [
            ['attribute' => 'id'],
            [
                'label'     => 'Titre',
                'attribute' => 'titre_2',
                'value'     => fn($m) => $m->getTitle(),
            ],
            ['attribute' => 'commune_depart'],
            [
                'label'     => 'Auteur',
                'attribute' => 'auteur',
                'value'     => fn($m) => $m->producteur?->raison_sociale ?? $m->auteur,
            ],
            [
                'label'     => 'Locomotion',
                'attribute' => 'locomotion_val',
                'value'     => fn($m) => $m->getLocomotionVal(),
            ],
            [
                'label'     => 'Difficulté',
                'attribute' => 'difficulte_val',
                'value'     => fn($m) => $m->getDifficulteVal(),
            ],
            [
                'label'  => 'Carte',
                'format' => 'raw',
                'value'  => function ($m) {
                    if ($m->hasCarteCache()) {
                        return '<span class="pill pill-ok"><span class="pill-dot"></span>' . Html::encode($m->getCarteCacheDate()) . '</span>';
                    }
                    return '<span class="pill pill-danger"><span class="pill-dot"></span>Absente</span>';
                },
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {carte}',
                'buttons'  => [
                    'carte' => fn($url, $m) => Html::a('Carte', ['/admin/itineraire/carte', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-secondary']),
                ],
                'urlCreator' => function ($action, $model) {
                    $map = ['view' => 'view', 'update' => 'update', 'delete' => 'delete'];
                    if (isset($map[$action])) {
                        return Url::to(['/admin/itineraire/' . $map[$action], 'id' => $model->id, 'lang' => 'fr']);
                    }
                    return '#';
                },
            ],
            [
                'label'  => 'Écran',
                'format' => 'raw',
                'value'  => fn($m) => Html::a('Écran', Url::to(['/topoguide/view', 'lang' => 'fr', 'id' => $m->id]), ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-secondary']),
            ],
            [
                'label'       => '<abbr title="WeasyPrint">weasy</abbr>',
                'encodeLabel' => false,
                'format'      => 'raw',
                'value'       => function ($m) {
                    return Html::a('HTML', Url::to(['/topoguide/weasy-debug', 'lang' => 'fr', 'id' => $m->id]), ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-secondary'])
                        . ' '
                        . Html::a('PDF', Url::to(['/topoguide/pdf-weasy', 'lang' => 'fr', 'id' => $m->id]), ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-warning']);
                },
            ],
            [
                'label'       => '<abbr title="PrinceXML">prince</abbr>',
                'encodeLabel' => false,
                'format'      => 'raw',
                'value'       => function ($m) {
                    return Html::a('HTML', Url::to(['/topoguide/prince-debug', 'lang' => 'fr', 'id' => $m->id]), ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-secondary'])
                        . ' '
                        . Html::a('PDF', Url::to(['/topoguide/pdf-prince', 'lang' => 'fr', 'id' => $m->id]), ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-danger']);
                },
            ],
        ],
    ]); ?>
</div>
