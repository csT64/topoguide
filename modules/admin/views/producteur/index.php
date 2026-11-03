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
  'tableOptions' => ['class' => 'table table-striped table-hover table-condensed'],
  'columns' => [
    [
      'attribute' => 'syndic_object_id',
      'label'     => 'Identifiant',
    ],
    [
      'attribute' => 'raison_sociale',
      'label'     => 'Nom',
    ],
    [
      'attribute' => 'commune',
      'label'     => 'Commune',
    ],
    [
      'attribute' => 'telephone',
      'label'     => 'Téléphone',
      'enableSorting' => false,
    ],
    [
      'label'  => 'Logo',
      'format' => 'raw',
      'enableSorting' => false,
      'value'  => fn($m) => $m->hasLogo()
          ? Html::img($m->getLogoUrl(), ['style' => 'max-height:36px; max-width:120px'])
          : '<span class="text-muted">—</span>',
    ],
    [
      'class'    => 'yii\grid\ActionColumn',
      'template' => '{view} {update} {delete}',
    ],
  ],
]); ?>
