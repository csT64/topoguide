<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;

/** @var array $statuts */

$this->title = 'Captures carte';
$this->params['breadcrumbs'][] = $this->title;

$presentes  = array_filter($statuts, fn($s) => $s['present']);
$manquantes = array_filter($statuts, fn($s) => !$s['present']);
?>
<div class="carte-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="page-header-sub">
                <span class="pill pill-ok"><span class="pill-dot"></span><?= count($presentes) ?> présentes</span>
                <span class="pill pill-danger ms-1"><span class="pill-dot"></span><?= count($manquantes) ?> manquantes</span>
            </p>
        </div>
        <div class="page-header-actions">
            <?= Bouton::post(
                Icon::svg('refresh') . ' Générer les manquantes',
                ['/admin/carte/generer-manquantes'],
                ['class' => 'btn btn-warning']
            ) ?>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2">
        <button class="btn btn-sm btn-primary active" onclick="filterRows('all')">Toutes</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="filterRows('present')">Présentes</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="filterRows('missing')">Manquantes</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <caption class="visually-hidden">Liste des captures carte par itinéraire</caption>
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Titre</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Date</th>
                    <th scope="col">Taille</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statuts as $s): ?>
                    <tr data-status="<?= $s['present'] ? 'present' : 'missing' ?>">
                        <td><span class="code-id"><?= Html::encode($s['id']) ?></span></td>
                        <td><?= Html::encode($s['titre']) ?></td>
                        <td>
                            <?php if ($s['present']): ?>
                                <span class="pill pill-ok"><span class="pill-dot"></span>Présente</span>
                            <?php else: ?>
                                <span class="pill pill-danger"><span class="pill-dot"></span>Manquante</span>
                            <?php endif ?>
                        </td>
                        <td class="texte-meta"><?= $s['date'] ?? '—' ?></td>
                        <td><?= $s['taille'] ?></td>
                        <td class="d-flex flex-wrap gap-1">
                            <?php if ($s['present']): ?>
                                <?= Html::a('Aperçu', ['/admin/carte/apercu', 'id' => $s['id']], ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-secondary']) ?>
                                <?= Bouton::post(Icon::svg('trash'), ['/admin/carte/supprimer', 'id' => $s['id']], ['class' => 'btn btn-sm btn-outline-danger', 'title' => 'Supprimer']) ?>
                            <?php endif ?>
                            <?= Bouton::post(Icon::svg('refresh'), ['/admin/itineraire/generer-carte', 'id' => $s['id']], ['class' => 'btn btn-sm btn-outline-warning', 'title' => 'Régénérer']) ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function filterRows(type) {
    document.querySelectorAll('tbody tr').forEach(function(tr) {
        if (type === 'all') tr.style.display = '';
        else if (type === 'present') tr.style.display = tr.dataset.status === 'present' ? '' : 'none';
        else tr.style.display = tr.dataset.status === 'missing' ? '' : 'none';
    });
}
</script>
