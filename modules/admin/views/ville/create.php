<?php
use yii\helpers\Html;

/** @var app\models\Ville $model */
$this->title = 'Nouvelle ville';
?>
<h2><?= Html::encode($this->title) ?></h2>
<?= $this->render('_form', ['model' => $model]) ?>
