<?php

use yii\helpers\Html;

/** @var app\models\Ville $model */

$this->title = 'Modifier : ' . $model->ville_id;
$this->params['breadcrumbs'][] = ['label' => 'Villes', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Modifier';
?>
<div class="ville-update">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a('← Retour', ['index'], ['class' => 'btn btn-link']) ?>
        </div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
