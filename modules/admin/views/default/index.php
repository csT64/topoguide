<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $stats */
/** @var int $nbProducteurs */
/** @var int $manquantes */
/** @var array $logLines */
$this->title = 'Tableau de bord';
?>
<h2>Tableau de bord</h2>

<div class="row">
  <div class="col-md-3">
    <div class="panel panel-info">
      <div class="panel-heading">Itinéraires FR</div>
      <div class="panel-body text-center"><h3><?= $stats['fr'] ?></h3></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="panel panel-info">
      <div class="panel-heading">Itinéraires EN</div>
      <div class="panel-body text-center"><h3><?= $stats['en'] ?></h3></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="panel panel-info">
      <div class="panel-heading">Itinéraires ES</div>
      <div class="panel-body text-center"><h3><?= $stats['es'] ?></h3></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="panel panel-default">
      <div class="panel-heading">Producteurs</div>
      <div class="panel-body text-center"><h3><?= $nbProducteurs ?></h3></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="panel panel-<?= $manquantes > 0 ? 'warning' : 'success' ?>">
      <div class="panel-heading">Captures carte manquantes</div>
      <div class="panel-body">
        <h3><?= $manquantes ?></h3>
        <?php if ($manquantes > 0): ?>
          <?= Html::a('Générer les captures manquantes', Url::to(['/admin/carte/generer-manquantes']), [
              'class' => 'btn btn-warning',
              'data-method' => 'post',
          ]) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="panel panel-default">
      <div class="panel-heading">
        Dernières erreurs <?= Html::a('Voir tout', ['/admin/log/index'], ['class' => 'btn btn-xs btn-default pull-right']) ?>
      </div>
      <div class="panel-body">
        <?php if (empty($logLines)): ?>
          <em>Aucune erreur.</em>
        <?php else: ?>
          <pre style="max-height:200px;overflow:auto;font-size:11px;"><?= Html::encode(implode("\n", $logLines)) ?></pre>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
