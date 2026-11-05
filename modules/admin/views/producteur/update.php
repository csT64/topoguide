<?php

use yii\helpers\Html;

/** @var app\models\Producteur $model */

$this->title = 'Modifier : ' . ($model->raison_sociale ?: $model->syndic_object_id);
$this->params['breadcrumbs'][] = ['label' => 'Producteurs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->raison_sociale ?: $model->syndic_object_id, 'url' => ['view', 'id' => $model->syndic_object_id]];
$this->params['breadcrumbs'][] = 'Modifier';
?>
<div class="producteur-update">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a('← Voir', ['view', 'id' => $model->syndic_object_id], ['class' => 'btn btn-link']) ?>
        </div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
