<?php

use yii\helpers\Html;

/** @var app\models\Producteur $model */

$this->title = 'Nouveau producteur';
$this->params['breadcrumbs'][] = ['label' => 'Producteurs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="producteur-create">
    <div class="page-header">
        <div><h1><?= Html::encode($this->title) ?></h1></div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
