<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\Producteur $model */
/** @var yii\web\View $this */
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

<div class="row">
  <div class="col-md-6">
    <?= $form->field($model, 'syndic_object_id')->textInput(['readonly' => !$model->isNewRecord]) ?>
    <?= $form->field($model, 'raison_sociale')->textInput() ?>
    <?= $form->field($model, 'adresse_1')->textInput() ?>
    <?= $form->field($model, 'adresse_2')->textInput() ?>
    <?= $form->field($model, 'adresse_3')->textInput() ?>
    <?= $form->field($model, 'code_postal')->textInput() ?>
    <?= $form->field($model, 'commune')->textInput() ?>
  </div>
  <div class="col-md-6">
    <?= $form->field($model, 'telephone')->textInput() ?>
    <?= $form->field($model, 'url')->textInput(['type' => 'url']) ?>

    <div class="form-group">
      <label class="control-label">Logo actuel</label>
      <?php if ($model->hasLogo()): ?>
        <div>
          <img src="<?= Html::encode($model->getLogoUrl()) ?>"
               style="max-height:80px; max-width:200px; border:1px solid #ddd; padding:4px; display:block; margin-bottom:6px">
          <small class="text-muted"><?= Html::encode($model->getLogoFilename()) ?></small>
        </div>
      <?php else: ?>
        <p class="text-muted">Aucun logo</p>
      <?php endif; ?>
    </div>

    <?= $form->field($model, 'logoFile')->fileInput(['accept' => 'image/*'])->label('Nouveau logo (PNG/JPG)') ?>
    <p class="help-block">Le fichier sera enregistré sous <code><?= Html::encode($model->syndic_object_id . '.png') ?></code>.</p>
  </div>
</div>

<div class="form-group" style="margin-top:15px">
  <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
  <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-default']) ?>
</div>

<?php ActiveForm::end(); ?>
