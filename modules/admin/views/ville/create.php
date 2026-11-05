<?php

use yii\helpers\Html;

/** @var app\models\Ville $model */

$this->title = 'Nouvelle ville';
$this->params['breadcrumbs'][] = ['label' => 'Villes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ville-create">
    <div class="page-header">
        <div><h1><?= Html::encode($this->title) ?></h1></div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
