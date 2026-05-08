<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\Itineraire $model */
/** @var yii\web\View $this */
?>
<?php $form = ActiveForm::begin(); ?>

<div class="row">
  <div class="col-md-6">
    <h4>Identité</h4>
    <?= $form->field($model, 'id')->textInput(['readonly' => !$model->isNewRecord]) ?>
    <?= $form->field($model, 'titre_2')->textInput() ?>
    <?= $form->field($model, 'raison_sociale')->textInput() ?>
    <?= $form->field($model, 'auteur')->textInput(['placeholder' => 'ID producteur']) ?>
    <?= $form->field($model, 'code_insee')->textInput() ?>
    <?= $form->field($model, 'commune_depart')->textInput() ?>
    <?= $form->field($model, 'commune_arrivee')->textInput() ?>
  </div>
  <div class="col-md-6">
    <h4>Caractéristiques</h4>
    <?= $form->field($model, 'distance_km')->textInput(['type' => 'number', 'step' => '0.1']) ?>
    <?= $form->field($model, 'denivele')->textInput(['type' => 'number']) ?>
    <?= $form->field($model, 'denivele_negatif_cumule')->textInput(['type' => 'number']) ?>
    <?= $form->field($model, 'duree_txt')->textInput() ?>
    <?= $form->field($model, 'latitude')->textInput(['type' => 'number', 'step' => '0.000001']) ?>
    <?= $form->field($model, 'longitude')->textInput(['type' => 'number', 'step' => '0.000001']) ?>
    <?= $form->field($model, 'balisage_couleur')->textInput() ?>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <h4>Textes</h4>
    <?= $form->field($model, 'descriptif')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'descriptif_mobile')->textarea(['rows' => 4]) ?>
    <?= $form->field($model, 'alerte_info')->textarea(['rows' => 3]) ?>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <h4>Fichiers</h4>
    <?= $form->field($model, 'doc_gpx')->textInput() ?>
    <?= $form->field($model, 'doc_kml')->textInput() ?>
    <?= $form->field($model, 'doc_kmz')->textInput() ?>
    <?= $form->field($model, 'video')->textInput() ?>
  </div>
  <div class="col-md-6">
    <h4>Options</h4>
    <?= $form->field($model, 'is_active')->checkbox() ?>
    <?= $form->field($model, 'homologue_ffr')->checkbox() ?>
    <?= $form->field($model, 'alerte')->checkbox() ?>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <h4>Données JSON <small class="text-muted">(édition avancée)</small></h4>
    <div class="row">
      <div class="col-md-6">
        <?= $form->field($model, 'difficulte')->textarea(['rows' => 2]) ?>
        <?= $form->field($model, 'type')->textarea(['rows' => 2]) ?>
        <?= $form->field($model, 'locomotion')->textarea(['rows' => 2]) ?>
        <?= $form->field($model, 'duree')->textarea(['rows' => 2]) ?>
      </div>
      <div class="col-md-6">
        <?= $form->field($model, 'equipement')->textarea(['rows' => 3]) ?>
        <?= $form->field($model, 'point_d_attention')->textarea(['rows' => 3]) ?>
      </div>
    </div>
  </div>
</div>

<div class="form-group" style="margin-top:20px">
  <?= Html::submitButton($model->isNewRecord ? 'Créer' : 'Enregistrer', ['class' => 'btn btn-primary']) ?>
  <?= Html::a('Annuler', ['index'], ['class' => 'btn btn-default']) ?>
</div>

<?php ActiveForm::end(); ?>
