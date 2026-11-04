<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $stats */

$this->title = 'Cache PDF / HTML';
?>

<div class="cache-index">
  <h1>Cache PDF / HTML</h1>

  <!-- Statistiques par langue -->
  <div class="row" style="margin-bottom:24px">
    <?php foreach ($stats as $lang => $counts): ?>
    <div class="col-sm-4">
      <div class="panel panel-default">
        <div class="panel-heading"><strong><?= strtoupper(Html::encode($lang)) ?></strong></div>
        <div class="panel-body">
          <table class="table table-condensed" style="margin:0">
            <tr><td>PDF</td><td class="text-right"><strong><?= $counts['pdf'] ?></strong></td></tr>
            <tr><td>HTML</td><td class="text-right"><strong><?= $counts['html'] ?></strong></td></tr>
          </table>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Actions de purge -->
  <div class="panel panel-default">
    <div class="panel-heading"><strong>Actions</strong></div>
    <div class="panel-body">

      <p>
        <?= Html::a('Purger les fiches inactives / supprimées', ['purge-inactive'], [
            'class' => 'btn btn-warning',
            'data-confirm' => 'Supprimer le cache de toutes les fiches is_active=0 ou deleted_at non nul ?',
            'data-method'  => 'post',
        ]) ?>
        &nbsp;
        <?= Html::a('Vider tout le cache', ['purge-all'], [
            'class' => 'btn btn-danger',
            'data-confirm' => 'Vider l\'intégralité du cache ? Les PDF et HTML seront régénérés à la prochaine demande.',
            'data-method'  => 'post',
        ]) ?>
      </p>

      <p class="text-muted" style="margin-top:12px;font-size:13px">
        Le cache est régénéré automatiquement à la prochaine requête.<br>
        La purge d'une fiche inactive ne rend pas la fiche accessible — elle restera en 404 tant que <code>is_active=0</code> ou <code>deleted_at</code> est renseigné.
      </p>

      <hr>

      <h4>Purger une fiche spécifique</h4>
      <?php
      $form = \yii\widgets\ActiveForm::begin([
          'action' => Url::to(['purge-fiche']),
          'method' => 'post',
          'options' => ['style' => 'display:flex;gap:8px;align-items:flex-end'],
      ]);
      ?>
        <div class="form-group" style="margin:0;flex:1">
          <?= Html::input('text', 'syndicId', '', [
              'class'       => 'form-control',
              'placeholder' => 'syndic_object_id  (ex : ITIAQU064V5C6JJI)',
          ]) ?>
        </div>
        <?= Html::submitButton('Purger', ['class' => 'btn btn-default']) ?>
      <?php \yii\widgets\ActiveForm::end(); ?>

    </div>
  </div>

  <!-- Rappel cycle de vie -->
  <div class="panel panel-info">
    <div class="panel-heading"><strong>Rappel — cycle de vie des fiches</strong></div>
    <div class="panel-body" style="font-size:13px">
      <p>
        Deux vérifications <strong>indépendantes</strong> sont effectuées à chaque requête :
      </p>
      <ol>
        <li><code>is_active = 0</code> <strong>ou</strong> <code>deleted_at IS NOT NULL</code> → HTTP 404 + purge du cache (quel que soit <code>updated_at</code>).</li>
        <li><code>updated_at</code> postérieur à <code>generated_at</code> (meta fichier) → régénération (indépendamment de <code>is_active</code>).</li>
      </ol>
      <p class="text-muted">
        <code>updated_at</code> n'est <strong>pas</strong> mis à jour lors d'une dépublication — seulement lors d'un changement de contenu ou d'une republication.
      </p>
    </div>
  </div>

</div>
