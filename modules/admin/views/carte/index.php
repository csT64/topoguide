<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $statuts */
$this->title = 'Gestion des captures carte';
$presentes  = array_filter($statuts, fn($s) => $s['present']);
$manquantes = array_filter($statuts, fn($s) => !$s['present']);
?>
<h2>Captures carte</h2>

<p>
  <strong><?= count($presentes) ?></strong> présentes —
  <strong><?= count($manquantes) ?></strong> manquantes
  <?= Html::a('Générer les manquantes', Url::to(['/admin/carte/generer-manquantes']), [
      'class' => 'btn btn-warning btn-sm',
      'data-method' => 'post',
  ]) ?>
</p>

<div id="filter-btns" class="btn-group" style="margin-bottom:10px;">
  <button class="btn btn-default btn-xs active" onclick="filterRows('all')">Toutes</button>
  <button class="btn btn-default btn-xs" onclick="filterRows('present')">Présentes</button>
  <button class="btn btn-default btn-xs" onclick="filterRows('missing')">Manquantes</button>
</div>

<table class="table table-striped table-condensed">
  <thead>
    <tr><th>ID</th><th>Titre</th><th>Statut</th><th>Date</th><th>Taille</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($statuts as $s): ?>
    <tr data-status="<?= $s['present'] ? 'present' : 'missing' ?>">
      <td><?= Html::encode($s['id']) ?></td>
      <td><?= Html::encode($s['titre']) ?></td>
      <td><?= $s['present']
          ? '<span class="label label-success">✓ Présente</span>'
          : '<span class="label label-danger">✗ Manquante</span>' ?>
      </td>
      <td><?= $s['date'] ?? '—' ?></td>
      <td><?= $s['taille'] ?></td>
      <td>
        <?php if ($s['present']): ?>
          <?= Html::a('Aperçu', ['/admin/carte/apercu', 'id' => $s['id']], ['target' => '_blank', 'class' => 'btn btn-xs btn-default']) ?>
          <?= Html::a('Supprimer', ['/admin/carte/supprimer', 'id' => $s['id']], ['class' => 'btn btn-xs btn-danger', 'data-method' => 'post']) ?>
        <?php endif; ?>
        <?= Html::a('Régénérer', ['/admin/itineraire/generer-carte', 'id' => $s['id']], ['class' => 'btn btn-xs btn-warning', 'data-method' => 'post']) ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
function filterRows(type) {
  document.querySelectorAll('tbody tr').forEach(function(tr) {
    if (type === 'all') { tr.style.display = ''; }
    else if (type === 'present') { tr.style.display = tr.dataset.status === 'present' ? '' : 'none'; }
    else { tr.style.display = tr.dataset.status === 'missing' ? '' : 'none'; }
  });
}
</script>
