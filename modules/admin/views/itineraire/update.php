<?php

use yii\helpers\Html;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */

$this->title = 'Modifier : ' . $model->getTitle();
$this->params['breadcrumbs'][] = ['label' => 'Itinéraires', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->getTitle() ?: $model->id, 'url' => ['view', 'id' => $model->id, 'lang' => Yii::$app->language]];
$this->params['breadcrumbs'][] = 'Modifier';
?>
<div class="itineraire-update">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a('← Voir', ['view', 'id' => $model->id, 'lang' => Yii::$app->language], ['class' => 'btn btn-link']) ?>
        </div>
    </div>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
