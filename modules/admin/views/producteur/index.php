<?php

use app\helpers\Icon;
use app\widgets\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\ProducteurSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Producteurs';
$this->params['breadcrumbs'][] = $this->title;

$onlyUsed  = (bool)($searchModel->onlyUsed ?? false);
$toggleUrl = Url::current(['ProducteurSearch[onlyUsed]' => $onlyUsed ? '0' : '1']);
?>
<div class="producteur-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a(
                $onlyUsed ? Icon::svg('checks') . ' Référencés dans les fiches' : 'Tous les producteurs',
                $toggleUrl,
                ['class' => 'btn btn-sm ' . ($onlyUsed ? 'btn-primary' : 'btn-outline-secondary')]
            ) ?>
            <?= Html::a(Icon::svg('plus') . ' Nouveau', ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?= GridView::widget([
        'caption'      => 'Liste des producteurs',
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns' => [
            ['attribute' => 'syndic_object_id', 'label' => 'Identifiant'],
            ['attribute' => 'raison_sociale', 'label' => 'Nom'],
            ['attribute' => 'commune', 'label' => 'Commune'],
            ['attribute' => 'telephone', 'label' => 'Téléphone', 'enableSorting' => false],
            [
                'label'         => 'Logo',
                'format'        => 'raw',
                'enableSorting' => false,
                'value'         => fn($m) => $m->hasLogo()
                    ? Html::img($m->getLogoUrl(), ['style' => 'max-height:36px;max-width:120px'])
                    : '<span class="text-body-secondary">—</span>',
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update} {delete}'],
        ],
    ]); ?>
</div>
