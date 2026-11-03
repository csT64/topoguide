<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array  $overview */
/** @var array  $sections */
/** @var string $generatedAt */

$this->title = 'Qualité des données';

$severityColor = [
    'error'   => 'danger',
    'warning' => 'warning',
    'info'    => 'info',
];

/**
 * Retourne la classe Bootstrap d'une ligne selon le résultat.
 */
$rowClass = function(array $check) use ($severityColor): string {
    if ($check['count'] === 0) return 'success';
    return $severityColor[$check['severity']] ?? 'active';
};

/**
 * Retourne une icône texte selon le résultat.
 */
$icon = function(array $check): string {
    if ($check['count'] === 0) return '✔';
    return match($check['severity']) {
        'error'   => '✖',
        'warning' => '⚠',
        default   => '·',
    };
};

/**
 * Retourne les liens des ID échantillon.
 * Pour la table producteur, on lie vers /admin/producteur.
 * Pour fr/en/es, on lie vers la vue admin itineraire.
 */
$sampleLinks = function(array $check): string {
    if (empty($check['sample'])) return '';
    $links = [];
    foreach ($check['sample'] as $id) {
        // Producteurs : syndic_object_id (commence par ORGAQU)
        if (str_starts_with((string)$id, 'ORGAQU') || str_starts_with((string)$id, 'ORG')) {
            $links[] = Html::a(
                Html::encode($id),
                Url::to(['/admin/producteur/view', 'id' => $id]),
                ['target' => '_blank', 'title' => 'Voir le producteur']
            );
        } else {
            $links[] = Html::a(
                Html::encode($id),
                Url::to(['/admin/itineraire/view', 'id' => $id, 'lang' => 'fr']),
                ['target' => '_blank', 'title' => 'Voir la fiche admin']
            );
        }
    }
    return implode(' ', $links);
};
?>

<div style="display:flex; align-items:baseline; gap:16px; margin-bottom:10px;">
  <h2 style="margin:0"><?= Html::encode($this->title) ?></h2>
  <small class="text-muted">Généré le <?= Html::encode($generatedAt) ?></small>
  <?= Html::a('&#8635; Actualiser', ['index'], ['class' => 'btn btn-default btn-xs']) ?>
</div>

<!-- ── Vue globale ─────────────────────────────────────────────────── -->
<div class="row" style="margin-bottom:10px;">

  <div class="col-md-2 col-sm-4">
    <div class="panel panel-primary" style="margin-bottom:8px;">
      <div class="panel-heading" style="padding:6px 10px;">Fiches FR</div>
      <div class="panel-body text-center" style="padding:8px;">
        <h3 style="margin:0;"><?= $overview['totalFr'] ?></h3>
        <small class="text-muted"><?= $overview['activeFr'] ?> actives</small>
      </div>
    </div>
  </div>

  <div class="col-md-2 col-sm-4">
    <?php $colorEn = $overview['pctEn'] >= 90 ? 'success' : ($overview['pctEn'] >= 50 ? 'warning' : 'danger'); ?>
    <div class="panel panel-<?= $colorEn ?>" style="margin-bottom:8px;">
      <div class="panel-heading" style="padding:6px 10px;">Fiches EN</div>
      <div class="panel-body text-center" style="padding:8px;">
        <h3 style="margin:0;"><?= $overview['totalEn'] ?></h3>
        <small><?= $overview['pctEn'] ?>% de couverture</small>
      </div>
    </div>
  </div>

  <div class="col-md-2 col-sm-4">
    <?php $colorEs = $overview['pctEs'] >= 90 ? 'success' : ($overview['pctEs'] >= 50 ? 'warning' : 'danger'); ?>
    <div class="panel panel-<?= $colorEs ?>" style="margin-bottom:8px;">
      <div class="panel-heading" style="padding:6px 10px;">Fiches ES</div>
      <div class="panel-body text-center" style="padding:8px;">
        <h3 style="margin:0;"><?= $overview['totalEs'] ?></h3>
        <small><?= $overview['pctEs'] ?>% de couverture</small>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6">
    <div class="panel panel-default" style="margin-bottom:8px;">
      <div class="panel-heading" style="padding:6px 10px;">Producteurs</div>
      <div class="panel-body text-center" style="padding:8px;">
        <h3 style="margin:0;"><?= $overview['totalProd'] ?></h3>
        <small class="text-muted"><?= $overview['prodRef'] ?> référencés dans les fiches</small>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6">
    <?php
    // Score global : nb de checks en erreur
    $nbErrors = 0; $nbWarnings = 0;
    foreach ($sections as $s) {
        foreach ($s['checks'] as $c) {
            if ($c['count'] > 0) {
                if ($c['severity'] === 'error')   $nbErrors++;
                if ($c['severity'] === 'warning')  $nbWarnings++;
            }
        }
    }
    $scoreColor = $nbErrors > 0 ? 'danger' : ($nbWarnings > 0 ? 'warning' : 'success');
    $scoreLabel = $nbErrors > 0 ? "$nbErrors erreur(s), $nbWarnings alerte(s)" : ($nbWarnings > 0 ? "$nbWarnings alerte(s)" : 'Tout est OK');
    ?>
    <div class="panel panel-<?= $scoreColor ?>" style="margin-bottom:8px;">
      <div class="panel-heading" style="padding:6px 10px;">Score global</div>
      <div class="panel-body text-center" style="padding:8px;">
        <h3 style="margin:0;"><?= $nbErrors + $nbWarnings ?></h3>
        <small><?= Html::encode($scoreLabel) ?></small>
      </div>
    </div>
  </div>

</div>

<!-- ── Sections de vérification ────────────────────────────────────── -->
<?php foreach ($sections as $section): ?>
<div class="panel panel-default" style="margin-bottom:14px;">
  <div class="panel-heading">
    <h4 style="margin:0;"><?= Html::encode($section['icon'] ?? '') ?> <?= Html::encode($section['title']) ?></h4>
  </div>
  <div class="panel-body" style="padding:0;">
    <table class="table table-condensed" style="margin:0;">
      <thead>
        <tr>
          <th style="width:32px;"></th>
          <th>Vérification</th>
          <th style="width:90px; text-align:right;">Défauts</th>
          <th style="width:200px;">Répartition</th>
          <th>Exemples d'ID</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($section['checks'] as $check): ?>
        <?php $rc = $rowClass($check); ?>
        <tr class="<?= $rc === 'success' ? '' : "warning-row-$rc" ?>" style="<?= $check['count'] > 0 ? "background-color: var(--row-bg-$rc, inherit);" : '' ?>">

          <td style="text-align:center; font-size:15px; padding:6px 4px;
            color:<?= match($rc) {
              'danger'  => '#a94442',
              'warning' => '#8a6d3b',
              'info'    => '#31708f',
              'success' => '#3c763d',
              default   => '#555'
            } ?>">
            <?= $icon($check) ?>
          </td>

          <td style="padding:6px 8px;">
            <?= Html::encode($check['label']) ?>
            <?php if (!empty($check['note'])): ?>
              <br><small class="text-muted"><?= Html::encode($check['note']) ?></small>
            <?php endif; ?>
          </td>

          <td style="text-align:right; padding:6px 8px; font-weight:<?= $check['count'] > 0 ? 'bold' : 'normal' ?>; color:<?= $check['count'] > 0 ? ($rc === 'danger' ? '#a94442' : ($rc === 'warning' ? '#8a6d3b' : '#31708f')) : '#3c763d' ?>">
            <?= $check['count'] > 0 ? $check['count'] . ' <small>(' . $check['pct'] . '%)</small>' : '—' ?>
          </td>

          <td style="padding:5px 8px; vertical-align:middle;">
            <?php if ($check['count'] > 0 && $check['pct'] > 0): ?>
            <div class="progress" style="margin:0; height:16px; background:#e8e8e8;">
              <div class="progress-bar progress-bar-<?= $rc !== 'success' ? $rc : 'success' ?>"
                   style="width:<?= max(3, $check['pct']) ?>%; min-width:3%; font-size:11px; line-height:16px;">
                <?= $check['pct'] > 8 ? $check['pct'] . '%' : '' ?>
              </div>
            </div>
            <?php else: ?>
            <span class="text-success" style="font-size:12px;">Aucun défaut</span>
            <?php endif; ?>
          </td>

          <td style="padding:6px 8px; font-size:12px; color:#555;">
            <?= $sampleLinks($check) ?>
            <?php if ($check['count'] > 5): ?>
              <span class="text-muted">… +<?= $check['count'] - 5 ?></span>
            <?php endif; ?>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<p class="text-muted" style="font-size:12px; margin-top:6px;">
  ✖ Erreur critique &nbsp;|&nbsp; ⚠ Avertissement &nbsp;|&nbsp; · Information &nbsp;|&nbsp; ✔ OK
  &nbsp;—&nbsp; Les liens ouvrent la fiche admin dans un nouvel onglet.
</p>
