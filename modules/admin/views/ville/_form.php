<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\Ville $model */
?>
<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'ville_id')->textInput(['readonly' => !$model->isNewRecord]) ?>
<?= $form->field($model, 'ville_code')->textInput(['placeholder' => 'Code INSEE']) ?>
<?= $form->field($model, 'default_zoom')->textInput(['type' => 'number', 'min' => 1, 'max' => 18]) ?>

<div class="form-group" style="margin-top:15px">
  <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
  <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-default']) ?>
</div>

<?php ActiveForm::end(); ?>
