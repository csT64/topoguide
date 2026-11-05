<?php

/**
 * Page spécimen de la charte graphique Tourisme 64.
 *
 * @var yii\web\View $this
 * @var yii\data\Pagination $pagination
 * @var string[] $icones
 */

use app\assets\ChartJsAsset;
use app\helpers\Icon;
use app\helpers\Bouton;
use app\widgets\ActiveForm;
use yii\bootstrap5\Html;
use app\widgets\LinkPager;
use yii\base\DynamicModel;

ChartJsAsset::register($this);
$this->title = 'Charte graphique';
$this->params['breadcrumbs'][] = 'Administration';
$this->params['breadcrumbs'][] = $this->title;

$jetons = [
    'Encre — texte, boutons principaux' => '--color-ink',
    'Papier — fond, texte sur aplat' => '--color-paper',
    'Surface — filtres, pied de page' => '--color-surface',
    'Surface 2 — pastille neutre' => '--color-surface-2',
    'Texte secondaire' => '--color-muted',
    'Bordure décorative' => '--color-border',
    'Contour des champs' => '--color-control-border',
    'Rouge vif — pictos, graphiques' => '--color-accent',
    'Rouge texte — liens, boutons' => '--color-accent-strong',
    'Rouge pâle' => '--color-accent-wash',
    'Jaune — fond seulement' => '--color-highlight',
    'Jaune pâle' => '--color-highlight-wash',
    'Texte sur jaune pâle' => '--color-highlight-text',
    'Vert — succès' => '--color-ok',
    'Vert pâle' => '--color-ok-wash',
    'Focus clavier' => '--color-focus',
    'Série de graphique' => '--viz-serie',
];
$exemple = new DynamicModel(['nom' => '', 'email' => 'pas-un-email', 'type' => 'a', 'suivi' => true, 'mode' => 'b', 'notes' => '']);
$exemple->addRule(['email'], 'email')->addRule(['nom'], 'required')->validate();
?>
<div class="charte-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="page-header-sub">Composants communs des applications Yii Tourisme 64 — référence : docs/charte-graphique/CHARTE_GRAPHIQUE.md (tourinsoft-client).</p>
        </div>
        <div class="page-header-actions">
            <?= Html::a(Icon::svg('download') . ' Exporter', '#', ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a(Icon::svg('plus') . ' Action principale', '#', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <section class="panel mb-4">
        <h2 id="couleurs-heading" class="section-title"><?= Icon::svg('settings') ?> Couleurs (thème courant)</h2>
        <div class="row g-2" id="nuancier">
            <?php foreach ($jetons as $nom => $var): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded border" style="width:44px;height:32px;background:var(<?= $var ?>)"></span>
                        <span><span class="fw-semibold"><?= Html::encode($nom) ?></span><br>
                            <span class="code-id"><?= $var ?> <span class="valeur" data-var="<?= $var ?>"></span></span></span>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <section class="panel mb-4">
        <h2 id="typo-heading" class="section-title"><?= Icon::svg('files') ?> Typographie</h2>
        <p class="h1 mb-1">Titre de page (h1) — Plus Jakarta Sans 800</p>
        <p class="h2 mb-1">Titre de section (h2)</p>
        <p class="h3 mb-2">Sous-titre (h3) — 700</p>
        <p>Texte courant — Inter 400, 16 px. <a href="#">Lien</a>, <strong>gras</strong>, <span class="text-body-secondary">texte secondaire</span>,
            <span class="code-id">ID-123456</span>, <span class="texte-meta">mis à jour le 4 nov. 2026</span>.</p>
        <p class="small mb-0">Petit texte (0,875 rem) <a href="#">avec un lien</a>.</p>
    </section>

    <h2 class="visually-hidden">Chiffres clés</h2>
    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('building-store', ['class' => 'ic-lg']) ?></div>
            <h3>Carte de chiffre clé</h3>
            <p class="kpi">16 645</p>
            <p class="stat-card-sub mb-0">dont 5 261 labellisées</p>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('trending-up', ['class' => 'ic-lg']) ?></div>
            <h3>Taux</h3>
            <p class="kpi">31,6 %</p>
            <div class="bar"><span style="width:31.6%"></span></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('clock-hour-4', ['class' => 'ic-lg']) ?></div>
            <h3>Dernière mise à jour</h3>
            <p class="kpi">4 nov.</p>
        </div>
    </div>

    <section class="panel mb-4">
        <h2 id="boutons-heading" class="section-title"><?= Icon::svg('circle-check') ?> Boutons, pastilles, badges</h2>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-primary"><?= Icon::svg('device-floppy') ?> Primaire</button>
            <button class="btn btn-outline-primary">Contour primaire</button>
            <button class="btn btn-secondary">Secondaire</button>
            <button class="btn btn-outline-secondary">Contour secondaire</button>
            <button class="btn btn-success">Succès</button>
            <button class="btn btn-outline-success">Contour succès</button>
            <button class="btn btn-danger"><?= Icon::svg('trash') ?> Danger</button>
            <button class="btn btn-outline-danger">Contour danger</button>
            <button class="btn btn-warning">Attention</button>
            <button class="btn btn-link">Lien</button>
            <button class="btn btn-primary" disabled>Désactivé</button>
        </div>
        <p class="small text-body-secondary">Action qui modifie des données : <code>Bouton::post()</code> (vrai bouton dans un formulaire POST) —
            <?= Bouton::post(Icon::svg('refresh') . ' Synchroniser', ['/charte/index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?></p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-sm btn-primary">Petit</button>
            <button class="btn btn-sm btn-outline-secondary">Petit contour</button>
            <button class="btn btn-lg btn-primary">Grand</button>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="pill pill-ok"><span class="pill-dot"></span>Validé</span>
            <span class="pill pill-warn"><span class="pill-dot"></span>À vérifier</span>
            <span class="pill pill-danger"><span class="pill-dot"></span>Erreur</span>
            <span class="pill pill-neutral"><span class="pill-dot"></span>Neutre</span>
            <span class="badge text-bg-secondary">12</span>
        </div>
    </section>

    <h2 class="visually-hidden">Filtres et formulaire</h2>
    <form class="filters row g-2 align-items-end" action="#" method="get">
        <div class="col-md-4">
            <label class="form-label" for="f-q">Recherche</label>
            <input class="form-control" id="f-q" name="q" placeholder="Libellé ou code…">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="f-type">Type</label>
            <select class="form-select" id="f-type" name="type"><option>Tous</option><option>Hébergement</option></select>
        </div>
        <div class="col-md-2 form-check ms-2 mb-2">
            <input class="form-check-input" type="checkbox" id="f-suivis" name="suivis" value="1" checked>
            <label class="form-check-label" for="f-suivis">Suivis</label>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary text-nowrap"><?= Icon::svg('list-search') ?> Filtrer</button>
            <a class="btn btn-link" href="#">Effacer</a>
        </div>
    </form>
    <?php $form = ActiveForm::begin(['action' => '#', 'options' => ['class' => 'mb-4']]); ?>
    <div class="panel">
        <?= $form->mentionObligatoire() ?>
        <?= $form->errorSummary($exemple) ?>
        <fieldset class="form-group-fieldset">
            <legend><?= Icon::svg('users') ?> Groupe de champs</legend>
            <div class="row g-3">
                <div class="col-md-6"><?= $form->field($exemple, 'nom')->label('Nom (champ en erreur)') ?></div>
                <div class="col-md-6"><?= $form->field($exemple, 'email')->label('E-mail')->hint('Adresse générique de préférence (contact@, info@…).') ?></div>
                <div class="col-md-6"><?= $form->field($exemple, 'type')->label('Liste')->dropDownList(['a' => 'Option A', 'b' => 'Option B']) ?></div>
                <div class="col-md-6"><?= $form->field($exemple, 'notes')->label('Zone de texte')->textarea(['rows' => 2]) ?></div>
                <div class="col-md-6"><?= $form->field($exemple, 'suivi')->checkbox(['label' => 'Case à cocher']) ?></div>
                <div class="col-md-6"><?= $form->field($exemple, 'mode')->label('Boutons radio')->radioList(['a' => 'Choix A', 'b' => 'Choix B']) ?></div>
            </div>
        </fieldset>
        <button type="button" class="btn btn-primary"><?= Icon::svg('device-floppy') ?> Enregistrer</button>
        <a class="btn btn-link" href="#">Annuler</a>
    </div>
    <?php ActiveForm::end(); ?>

    <section class="mb-4">
        <h2 class="visually-hidden">Messages</h2>
        <div class="alert alert-success" role="status">Message de succès (Yii::$app->session->setFlash('success', …)).</div>
        <div class="alert alert-info" role="status">Message d'information.</div>
        <div class="alert alert-warning" role="alert">Message d'avertissement.</div>
        <div class="alert alert-danger" role="alert">Message d'erreur.</div>
    </section>

    <section class="panel mb-4">
        <h2 id="liste-heading" class="section-title"><?= Icon::svg('table') ?> Liste <span class="badge text-bg-secondary">240</span></h2>
        <div class="grid-view">
            <div class="summary">Affichage de 41-60 sur 240 éléments.</div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <caption class="visually-hidden">Exemple de liste de labels</caption>
                    <thead><tr>
                        <th scope="col"><a class="asc" href="#">Nom<span class="visually-hidden"> (trié par ordre croissant)</span></a></th><th scope="col"><a href="#">Code<span class="visually-hidden"> (cliquer pour trier)</span></a></th><th scope="col">État</th><th scope="col" class="text-end"><a class="desc" href="#">Offres<span class="visually-hidden"> (trié par ordre décroissant)</span></a></th><th scope="col">Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ([['Gîtes de France', 'GF', 'ok', 'Suivi', 4170], ['Qualité Tourisme', 'QT', 'warn', 'À vérifier', 3261], ['Pays Basque Sud', 'PBS', 'neutral', 'Non suivi', 134]] as [$n, $c, $p, $e, $o]): ?>
                        <tr>
                            <td><a class="fw-semibold" href="#"><?= Html::encode($n) ?></a></td>
                            <td><span class="code-id"><?= $c ?></span></td>
                            <td><span class="pill pill-<?= $p ?>"><span class="pill-dot"></span><?= $e ?></span></td>
                            <td class="text-end"><?= Yii::$app->formatter->asInteger($o) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="#">Ne plus suivre</a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?= LinkPager::widget(['pagination' => $pagination]) ?>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <section class="panel h-100">
                <h2 id="contact-heading" class="section-title"><?= Icon::svg('address-book') ?> Coordonnées</h2>
                <div class="contact-bloc">
                    <h3>Organisme</h3>
                    <p class="contact-nom mb-1">Fédération exemple</p>
                    <ul class="coordonnees list-unstyled mb-0">
                        <li><?= Icon::svg('world') ?> <a href="#">exemple.fr</a></li>
                        <li><?= Icon::svg('mail') ?> <a href="#">contact@exemple.fr</a></li>
                        <li><?= Icon::svg('phone') ?> <a href="#">05 59 00 00 00</a></li>
                        <li><?= Icon::svg('map-pin') ?> 1 place de l'Exemple, 64000 Pau</li>
                    </ul>
                </div>
                <div class="contact-bloc">
                    <h3>Relais local</h3>
                    <p class="text-body-secondary small mb-0">Aucune coordonnée.</p>
                </div>
                <div class="hint-block mt-3">Bloc d'aide (hint-block) : explication d'un champ ou d'une règle.</div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="panel h-100">
                <h2 id="graphique-heading" class="section-title"><?= Icon::svg('chart-bar') ?> Graphique et carte de chaleur</h2>
                <div class="chart-box" style="height:160px"><canvas id="chart-specimen" role="img" aria-label="Exemple de graphique en barres : offres par label"></canvas></div>
                <div class="table-responsive mt-3">
                    <table class="heatmap">
                        <caption class="visually-hidden">Exemple de carte de chaleur : offres par label et par bordereau</caption>
                        <thead><tr><th scope="col">Label</th><?php foreach (['HOT', 'HPA', 'LOI', 'RES'] as $b): ?><th scope="col"><?= $b ?></th><?php endforeach ?></tr></thead>
                        <tbody>
                        <?php foreach ([['Gîtes de France', [1, .62, .3, 0]], ['Qualité Tourisme', [.85, .45, .12, .05]]] as [$n, $vals]): ?>
                            <tr><th scope="row"><?= Html::encode($n) ?></th>
                            <?php foreach ($vals as $t): ?>
                                <?php if ($t > 0): ?>
                                    <td class="cellule" style="--t: <?= $t ?>"><a href="#"><?= round($t * 412) ?></a></td>
                                <?php else: ?>
                                    <td class="vide"><span>·</span></td>
                                <?php endif ?>
                            <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <section class="panel mb-4">
        <h2 id="icones-heading" class="section-title"><?= Icon::svg('award') ?> Icônes (Tabler, web/icons/)</h2>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-2">
            <?php foreach ($icones as $nom): ?>
                <div class="col"><span class="d-inline-flex align-items-center gap-2"><?= Icon::svg($nom, ['class' => 'ic-lg']) ?> <span class="code-id"><?= Html::encode($nom) ?></span></span></div>
            <?php endforeach ?>
        </div>
    </section>
</div>
<?php
$this->registerJs(<<<JS
(function () {
    var s = getComputedStyle(document.documentElement);
    document.querySelectorAll('#nuancier .valeur').forEach(function (el) {
        el.textContent = s.getPropertyValue(el.dataset.var).trim();
    });
    var c = { serie: s.getPropertyValue('--viz-serie').trim(), texte: s.getPropertyValue('--color-ink').trim(),
              secondaire: s.getPropertyValue('--color-muted').trim(), grille: s.getPropertyValue('--color-border').trim() };
    new Chart(document.getElementById('chart-specimen'), {
        type: 'bar',
        data: { labels: ['Gîtes de France', 'Qualité Tourisme', 'Accueil Vélo', 'Clévacances'],
                datasets: [{ data: [4170, 3261, 2358, 1450], backgroundColor: c.serie, borderRadius: 4, maxBarThickness: 28 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
                   scales: { x: { grid: { color: c.grille }, ticks: { color: c.secondaire } },
                             y: { grid: { display: false }, ticks: { color: c.texte } } } }
    });
})();
JS
);
