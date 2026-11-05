<?php

use app\widgets\ActiveForm;
use yii\helpers\Html;

/** @var app\models\Ville $model */

$form = ActiveForm::begin();
?>
<?= $form->mentionObligatoire() ?>

<?= $form->field($model, 'ville_id')->textInput(['readonly' => !$model->isNewRecord]) ?>
<?= $form->field($model, 'ville_code')->textInput(['placeholder' => 'Code INSEE']) ?>
<?= $form->field($model, 'default_zoom')->textInput(['type' => 'number', 'min' => 1, 'max' => 18]) ?>

<div class="mt-3 d-flex gap-2">
    <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-link']) ?>
</div>

<?php ActiveForm::end(); ?>
