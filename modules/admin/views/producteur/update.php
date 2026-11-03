<?php
use yii\helpers\Html;

/** @var app\models\Producteur $model */
$this->title = 'Modifier : ' . ($model->raison_sociale ?: $model->syndic_object_id);
?>
<h2><?= Html::encode($this->title) ?></h2>
<p><?= Html::a('← Voir', ['view', 'id' => $model->syndic_object_id], ['class' => 'btn btn-link btn-sm']) ?></p>
<?= $this->render('_form', ['model' => $model]) ?>
