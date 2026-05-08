<?php
use yii\helpers\Html;

/** @var app\models\Ville $model */
$this->title = 'Modifier : ' . $model->ville_id;
?>
<h2><?= Html::encode($this->title) ?></h2>
<p><?= Html::a('← Retour', ['index'], ['class' => 'btn btn-link btn-sm']) ?></p>
<?= $this->render('_form', ['model' => $model]) ?>
