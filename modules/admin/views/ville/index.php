<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var app\models\VilleSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Villes';
?>
<h2>
  <?= Html::encode($this->title) ?>
  <?= Html::a('+ Nouvelle', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
</h2>

<?= GridView::widget([
  'dataProvider' => $dataProvider,
  'filterModel'  => $searchModel,
  'columns' => [
    'ville_id',
    'ville_code',
    'default_zoom',
    [
      'class'    => 'yii\grid\ActionColumn',
      'template' => '{update} {delete}',
    ],
  ],
]); ?>
