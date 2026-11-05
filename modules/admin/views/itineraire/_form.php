<?php

use app\widgets\ActiveForm;
use yii\helpers\Html;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */

$form = ActiveForm::begin();
?>
<?= $form->mentionObligatoire() ?>

<div class="row g-3">
    <div class="col-md-6">
        <fieldset class="form-group-fieldset">
            <legend>Identité</legend>
            <div class="row g-3">
                <div class="col-12"><?= $form->field($model, 'id')->textInput(['readonly' => !$model->isNewRecord]) ?></div>
                <div class="col-12"><?= $form->field($model, 'titre_2')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'raison_sociale')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'auteur')->textInput(['placeholder' => 'ID producteur']) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'code_insee')->textInput() ?></div>
                <div class="col-md-6"><?= $form->field($model, 'commune_depart')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'commune_arrivee')->textInput() ?></div>
            </div>
        </fieldset>
    </div>
    <div class="col-md-6">
        <fieldset class="form-group-fieldset">
            <legend>Caractéristiques</legend>
            <div class="row g-3">
                <div class="col-md-6"><?= $form->field($model, 'distance_km')->textInput(['type' => 'number', 'step' => '0.1']) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'denivele')->textInput(['type' => 'number']) ?></div>
                <div class="col-12"><?= $form->field($model, 'denivele_negatif_cumule')->textInput(['type' => 'number']) ?></div>
                <div class="col-12"><?= $form->field($model, 'duree_txt')->textInput() ?></div>
                <div class="col-md-6"><?= $form->field($model, 'latitude')->textInput(['type' => 'number', 'step' => '0.000001']) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'longitude')->textInput(['type' => 'number', 'step' => '0.000001']) ?></div>
                <div class="col-12"><?= $form->field($model, 'balisage_couleur')->textInput() ?></div>
            </div>
        </fieldset>
    </div>
</div>

<fieldset class="form-group-fieldset mt-3">
    <legend>Textes</legend>
    <div class="row g-3">
        <div class="col-12"><?= $form->field($model, 'descriptif')->textarea(['rows' => 6]) ?></div>
        <div class="col-12"><?= $form->field($model, 'descriptif_mobile')->textarea(['rows' => 4]) ?></div>
        <div class="col-12"><?= $form->field($model, 'alerte_info')->textarea(['rows' => 3]) ?></div>
    </div>
</fieldset>

<div class="row g-3 mt-0">
    <div class="col-md-6">
        <fieldset class="form-group-fieldset">
            <legend>Fichiers</legend>
            <div class="row g-3">
                <div class="col-12"><?= $form->field($model, 'doc_gpx')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'doc_kml')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'doc_kmz')->textInput() ?></div>
                <div class="col-12"><?= $form->field($model, 'video')->textInput() ?></div>
            </div>
        </fieldset>
    </div>
    <div class="col-md-6">
        <fieldset class="form-group-fieldset">
            <legend>Options</legend>
            <?= $form->field($model, 'is_active')->checkbox(['label' => 'Actif']) ?>
            <?= $form->field($model, 'homologue_ffr')->checkbox(['label' => 'Homologué FFR']) ?>
            <?= $form->field($model, 'alerte')->checkbox(['label' => 'Alerte']) ?>
        </fieldset>
    </div>
</div>

<fieldset class="form-group-fieldset mt-3">
    <legend>Données JSON <span class="text-body-secondary fw-normal">(édition avancée)</span></legend>
    <div class="row g-3">
        <div class="col-md-6"><?= $form->field($model, 'difficulte')->textarea(['rows' => 2]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'type')->textarea(['rows' => 2]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'locomotion')->textarea(['rows' => 2]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'duree')->textarea(['rows' => 2]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'equipement')->textarea(['rows' => 3]) ?></div>
        <div class="col-md-6"><?= $form->field($model, 'point_d_attention')->textarea(['rows' => 3]) ?></div>
    </div>
</fieldset>

<div class="mt-3 d-flex gap-2">
    <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-link']) ?>
</div>

<?php ActiveForm::end(); ?>
