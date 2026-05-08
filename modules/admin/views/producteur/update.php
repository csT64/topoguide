<?php
use yii\helpers\Html;

/** @var app\models\Producteur $model */
$this->title = 'Modifier : ' . ($model->raison_sociale ?: $model->id);
?>
<h2><?= Html::encode($this->title) ?></h2>
<p><?= Html::a('← Voir', ['view', 'id' => $model->id], ['class' => 'btn btn-link btn-sm']) ?></p>
<?= $this->render('_form', ['model' => $model]) ?>
