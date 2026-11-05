<?php

use app\widgets\ActiveForm;
use yii\helpers\Html;

/** @var app\models\Producteur $model */
/** @var yii\web\View $this */

$form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
?>
<?= $form->mentionObligatoire() ?>

<div class="row g-3">
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

        <?php if ($model->hasLogo()): ?>
            <div class="mb-3">
                <p class="form-label mb-1">Logo actuel</p>
                <img src="<?= Html::encode($model->getLogoUrl()) ?>"
                     style="max-height:80px;max-width:200px;border:1px solid var(--color-border);padding:4px;border-radius:4px;display:block;margin-bottom:4px">
                <span class="texte-meta"><?= Html::encode($model->getLogoFilename()) ?></span>
            </div>
        <?php endif ?>

        <?= $form->field($model, 'logoFile')->fileInput(['accept' => 'image/*'])->label('Nouveau logo (PNG/JPG)') ?>
        <p class="hint-block">Le fichier sera enregistré sous <code><?= Html::encode($model->syndic_object_id . '.png') ?></code>.</p>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-link']) ?>
</div>

<?php ActiveForm::end(); ?>
