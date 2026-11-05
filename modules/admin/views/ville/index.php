<?php

use app\helpers\Icon;
use app\widgets\GridView;
use yii\helpers\Html;

/** @var app\models\VilleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Villes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ville-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a(Icon::svg('plus') . ' Nouvelle', ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?= GridView::widget([
        'caption'      => 'Liste des villes',
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns' => [
            'ville_id',
            'ville_code',
            'default_zoom',
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]); ?>
</div>
