<?php
use yii\helpers\Html;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */
$this->title = 'Modifier : ' . $model->getTitle();
?>
<h2><?= Html::encode($this->title) ?></h2>
<p>
  <?= Html::a('← Voir', ['view', 'id' => $model->id, 'lang' => Yii::$app->language], ['class' => 'btn btn-link btn-sm']) ?>
</p>

<?= $this->render('_form', ['model' => $model]) ?>
