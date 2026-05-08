<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var app\models\Producteur $model */
/** @var yii\web\View $this */
$this->title = $model->raison_sociale ?: $model->id;
?>
<h2><?= Html::encode($this->title) ?></h2>

<p>
  <?= Html::a('Modifier', ['update', 'id' => $model->id], ['class' => 'btn btn-warning btn-sm']) ?>
  <?= Html::a('Supprimer', ['delete', 'id' => $model->id], [
    'class' => 'btn btn-danger btn-sm',
    'data'  => ['confirm' => 'Supprimer ce producteur ?', 'method' => 'post'],
  ]) ?>
  <?= Html::a('Retour', ['index'], ['class' => 'btn btn-link btn-sm']) ?>
</p>

<div class="row">
  <div class="col-md-6">
    <?= DetailView::widget([
      'model' => $model,
      'attributes' => [
        'id', 'raison_sociale',
        'adresse_1', 'adresse_2', 'adresse_3',
        'code_postal', 'commune',
        'telephone', 'url',
      ],
    ]) ?>
  </div>
  <div class="col-md-6">
    <h4>Logo</h4>
    <?php if ($model->logo): ?>
      <img src="<?= Html::encode(Yii::$app->request->baseUrl . '/producteur/' . $model->logo) ?>"
           style="max-width:300px; border:1px solid #ddd; padding:8px">
      <p class="text-muted" style="margin-top:5px"><?= Html::encode($model->logo) ?></p>
    <?php else: ?>
      <p class="text-muted">Aucun logo</p>
    <?php endif; ?>
  </div>
</div>
