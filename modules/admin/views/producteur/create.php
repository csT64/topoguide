<?php
use yii\helpers\Html;

/** @var app\models\Producteur $model */
$this->title = 'Nouveau producteur';
?>
<h2><?= Html::encode($this->title) ?></h2>
<?= $this->render('_form', ['model' => $model]) ?>
