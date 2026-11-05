<?php

use app\helpers\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array  $overview */
/** @var array  $sections */
/** @var string $generatedAt */

$this->title = 'Qualité des données';
$this->params['breadcrumbs'][] = $this->title;

// Comptage global
$nbErrors = 0;
$nbWarnings = 0;
foreach ($sections as $s) {
    foreach ($s['checks'] as $c) {
        if ($c['count'] > 0) {
            if ($c['severity'] === 'error')   $nbErrors++;
            if ($c['severity'] === 'warning') $nbWarnings++;
        }
    }
}

/** Retourne la variante pill en fonction de la sévérité et du résultat. */
$pillVariant = function(array $check): string {
    if ($check['count'] === 0) return 'pill-ok';
    return match($check['severity']) {
        'error'   => 'pill-danger',
        'warning' => 'pill-warn',
        default   => 'pill-neutral',
    };
};

/** Liens vers les fiches admin pour les ID d'échantillon. */
$sampleLinks = function(array $check): string {
    if (empty($check['sample'])) return '';
    $links = [];
    foreach ($check['sample'] as $id) {
        if (str_starts_with((string)$id, 'ORGAQU') || str_starts_with((string)$id, 'ORG')) {
            $links[] = Html::a(Html::encode($id), Url::to(['/admin/producteur/view', 'id' => $id]), ['target' => '_blank', 'title' => 'Voir le producteur']);
        } else {
            $links[] = Html::a(Html::encode($id), Url::to(['/admin/itineraire/view', 'id' => $id, 'lang' => 'fr']), ['target' => '_blank', 'title' => 'Voir la fiche admin']);
        }
    }
    return implode(' ', $links);
};

$pctEn = $overview['pctEn'];
$pctEs = $overview['pctEs'];
?>
<div class="qualite-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="page-header-sub">Généré le <?= Html::encode($generatedAt) ?></p>
        </div>
        <div class="page-header-actions">
            <?= Html::a(Icon::svg('refresh') . ' Actualiser', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <!-- Vue globale -->
    <h2 class="visually-hidden">Chiffres clés</h2>
    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Fiches FR</h3>
            <p class="kpi"><?= $overview['totalFr'] ?></p>
            <p class="stat-card-sub mb-0"><?= $overview['activeFr'] ?> actives</p>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Fiches EN</h3>
            <p class="kpi"><?= $overview['totalEn'] ?></p>
            <div class="bar mb-1"><span style="width:<?= $pctEn ?>%"></span></div>
            <p class="stat-card-sub mb-0">
                <span class="pill <?= $pctEn >= 90 ? 'pill-ok' : ($pctEn >= 50 ? 'pill-warn' : 'pill-danger') ?>">
                    <span class="pill-dot"></span><?= $pctEn ?>% couverture
                </span>
            </p>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Fiches ES</h3>
            <p class="kpi"><?= $overview['totalEs'] ?></p>
            <div class="bar mb-1"><span style="width:<?= $pctEs ?>%"></span></div>
            <p class="stat-card-sub mb-0">
                <span class="pill <?= $pctEs >= 90 ? 'pill-ok' : ($pctEs >= 50 ? 'pill-warn' : 'pill-danger') ?>">
                    <span class="pill-dot"></span><?= $pctEs ?>% couverture
                </span>
            </p>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('building-community', ['class' => 'ic-lg']) ?></div>
            <h3>Producteurs</h3>
            <p class="kpi"><?= $overview['totalProd'] ?></p>
            <p class="stat-card-sub mb-0"><?= $overview['prodRef'] ?> référencés dans les fiches</p>
        </div>

        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('list-check', ['class' => 'ic-lg']) ?></div>
            <h3>Score global</h3>
            <p class="kpi"><?= $nbErrors + $nbWarnings ?></p>
            <p class="stat-card-sub mb-0">
                <?php if ($nbErrors === 0 && $nbWarnings === 0): ?>
                    <span class="pill pill-ok"><span class="pill-dot"></span>Tout est OK</span>
                <?php elseif ($nbErrors > 0): ?>
                    <span class="pill pill-danger"><span class="pill-dot"></span><?= $nbErrors ?> erreur(s)</span>
                    <?php if ($nbWarnings > 0): ?>
                        <span class="pill pill-warn ms-1"><span class="pill-dot"></span><?= $nbWarnings ?> alerte(s)</span>
                    <?php endif ?>
                <?php else: ?>
                    <span class="pill pill-warn"><span class="pill-dot"></span><?= $nbWarnings ?> alerte(s)</span>
                <?php endif ?>
            </p>
        </div>
    </div>

    <!-- Sections de vérification -->
    <?php foreach ($sections as $section): ?>
        <section class="panel mb-4">
            <h2 class="section-title">
                <?= Icon::svg($section['icone'] ?? 'circle-check') ?>
                <?= Html::encode($section['title']) ?>
            </h2>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <caption class="visually-hidden">Vérifications : <?= Html::encode($section['title']) ?></caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width:120px">État</th>
                            <th scope="col">Vérification</th>
                            <th scope="col" style="width:100px" class="text-end">Défauts</th>
                            <th scope="col" style="width:200px">Répartition</th>
                            <th scope="col">Exemples</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section['checks'] as $check): ?>
                            <tr>
                                <td>
                                    <span class="pill <?= $pillVariant($check) ?>">
                                        <span class="pill-dot"></span>
                                        <?php if ($check['count'] === 0): ?>OK
                                        <?php elseif ($check['severity'] === 'error'): ?>Erreur
                                        <?php elseif ($check['severity'] === 'warning'): ?>Alerte
                                        <?php else: ?>Info
                                        <?php endif ?>
                                    </span>
                                </td>
                                <td>
                                    <?= Html::encode($check['label']) ?>
                                    <?php if (!empty($check['note'])): ?>
                                        <br><span class="texte-meta"><?= Html::encode($check['note']) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end fw-<?= $check['count'] > 0 ? 'semibold' : 'normal' ?>">
                                    <?= $check['count'] > 0 ? $check['count'] . ' <small>(' . $check['pct'] . '%)</small>' : '—' ?>
                                </td>
                                <td>
                                    <?php if ($check['count'] > 0 && $check['pct'] > 0): ?>
                                        <div class="bar"><span style="width:<?= max(3, $check['pct']) ?>%"></span></div>
                                    <?php else: ?>
                                        <span class="texte-meta">Aucun défaut</span>
                                    <?php endif ?>
                                </td>
                                <td class="small">
                                    <?= $sampleLinks($check) ?>
                                    <?php if ($check['count'] > 5): ?>
                                        <span class="text-body-secondary">… +<?= $check['count'] - 5 ?></span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach ?>

    <p class="texte-meta">Les liens ouvrent la fiche admin dans un nouvel onglet.</p>
</div>
