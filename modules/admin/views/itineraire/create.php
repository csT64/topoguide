<?php
use yii\helpers\Html;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */
$this->title = 'Nouvel itinéraire';
?>
<h2><?= Html::encode($this->title) ?></h2>

<?= $this->render('_form', ['model' => $model]) ?>
