<?php

use yii\helpers\Html;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */

$this->title = 'Nouvel itinéraire';
$this->params['breadcrumbs'][] = ['label' => 'Itinéraires', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="itineraire-create">
    <div class="page-header">
        <div><h1><?= Html::encode($this->title) ?></h1></div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
