<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/** @var app\models\ItineraireSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Itinéraires';
?>
<h2>Itinéraires

<?php
$langues = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES'];
$current = $searchModel->langue;
foreach ($langues as $code => $label):
    $class = $code === $current ? 'btn btn-primary btn-xs' : 'btn btn-default btn-xs';
    echo Html::a($label, Url::current(['ItineraireSearch[langue]' => $code]), ['class' => $class]) . ' ';
endforeach;
?>
</h2>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'columns' => [
        [
            'attribute' => 'id',
            'format'    => 'text',
        ],
        [
            'label'     => 'Titre',
            'attribute' => 'titre_2',
            'value'     => fn($m) => $m->getTitle(),
        ],
        [
            'attribute' => 'commune_depart',
        ],
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
                    $date = $m->getCarteCacheDate();
                    return '<span class="label label-success">✓</span> <small>' . $date . '</small>';
                }
                return '<span class="label label-danger">✗</span>';
            },
        ],
        [
            'class'    => 'yii\grid\ActionColumn',
            'template' => '{view} {update} {delete} {html} {html_wk} {html_wp} {pdf_wk} {pdf_wp} {carte}',
            'buttons'  => [
                'html'     => fn ($url, $m) => Html::a('Écran',    Url::to(['/topoguide/view',       'lang' => 'fr', 'id' => $m->id]),                    ['target' => '_blank', 'class' => 'btn btn-xs btn-success']),
                'html_wk'  => fn ($url, $m) => Html::a('WK:HTML',  Url::to(['/topoguide/pdf-debug',  'lang' => 'fr', 'id' => $m->id, 'part' => 'content']), ['target' => '_blank', 'class' => 'btn btn-xs btn-default']),
                'html_wp'  => fn ($url, $m) => Html::a('WP:HTML',  Url::to(['/topoguide/weasy-debug','lang' => 'fr', 'id' => $m->id]),                    ['target' => '_blank', 'class' => 'btn btn-xs btn-default']),
                'pdf_wk'   => fn ($url, $m) => Html::a('WK:PDF',   Url::to(['/topoguide/pdf-wk',     'lang' => 'fr', 'id' => $m->id]),                    ['target' => '_blank', 'class' => 'btn btn-xs btn-info']),
                'pdf_wp'   => fn ($url, $m) => Html::a('WP:PDF',   Url::to(['/topoguide/pdf-weasy',  'lang' => 'fr', 'id' => $m->id]),                    ['target' => '_blank', 'class' => 'btn btn-xs btn-warning']),
                'carte'    => fn ($url, $m) => Html::a('Carte ✎',  ['/admin/itineraire/carte',        'id' => $m->id],                                     ['class'  => 'btn btn-xs btn-default']),
            ],
            'urlCreator' => function ($action, $model) {
                $map = ['view' => 'view', 'update' => 'update', 'delete' => 'delete'];
                if (isset($map[$action])) {
                    return Url::to(['/admin/itineraire/' . $map[$action], 'id' => $model->id, 'lang' => 'fr']);
                }
                return '#';
            },
        ],
    ],
]); ?>
