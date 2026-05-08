<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var app\models\ProducteurSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Producteurs';
?>
<h2>
  <?= Html::encode($this->title) ?>
  <?= Html::a('+ Nouveau', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
</h2>

<?= GridView::widget([
  'dataProvider' => $dataProvider,
  'filterModel'  => $searchModel,
  'columns' => [
    'id',
    'raison_sociale',
    'commune',
    'telephone',
    [
      'label'  => 'Logo',
      'format' => 'raw',
      'value'  => fn($m) => $m->logo
          ? Html::img(Yii::$app->request->baseUrl . '/producteur/' . $m->logo, ['style' => 'max-height:32px'])
          : '<span class="text-muted">—</span>',
    ],
    ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update} {delete}'],
  ],
]); ?>
