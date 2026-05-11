<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Producteur;

/** @var yii\web\View $this */
/** @var app\models\Itineraire $model */
/** @var string $lang */
/** @var bool $forPdf */

$forPdf = $forPdf ?? false;

// ── Données ──────────────────────────────────────────────────────────────────
$titre       = $model->getTitle();
$commune     = (string)($model->commune_depart  ?? '');
$communeArr  = (string)($model->commune_arrivee ?? '');
$type        = $model->getTypeVal();
$difficulte  = $model->getDifficulteVal();
$duree       = $model->getDureeVal();
$distance    = $model->distance_km;
$denivele    = $model->denivele;
$deniveleNeg = $model->denivele_negatif_cumule;
$boucle      = $model->getBoucleVal();
$descriptif  = (string)($model->descriptif      ?? '');
$parking     = (string)($model->parking         ?? '');
$balisage    = (string)($model->balisage_fichier ?? '');
$homologue   = !empty($model->homologue_ffr);
$photos      = $model->getPhotos();
$etapes      = $model->getEtapes();
$poi         = $model->getPoi();
$equipements = $model->getEquipements();
$attentions  = $model->getAttentions();

// ── Producteur ───────────────────────────────────────────────────────────────
$producteurId = $model->getProducteurId();
$producteur   = Producteur::findOne($producteurId);
$dept         = $model->getDept();

// ── Chemins & sources d'images ───────────────────────────────────────────────
$webroot = Yii::getAlias('@webroot');
$pix     = $webroot . '/pix/pdf';
$cdn     = Yii::$app->params['mediaCdnUrl'];

// Convertit un chemin local en attribut src selon le contexte
$src = function (string $path) use ($forPdf, $webroot): string {
    if (!file_exists($path)) return '';
    return $forPdf ? 'file://' . $path : str_replace($webroot, '', $path);
};

// Logo producteur (par ID, fallback département)
$logoPath   = $webroot . '/producteur/' . $producteurId . '.png';
$logoFallbk = $pix . '/departement/logo' . $dept . '.png';
$logoSrc    = file_exists($logoPath)   ? $src($logoPath) :
             (file_exists($logoFallbk) ? $src($logoFallbk) : null);

// Logo département (coin droit)
$deptLogoPath = $pix . '/departement/logo' . $dept . '.png';
$deptLogoSrc  = file_exists($deptLogoPath) ? $src($deptLogoPath) : null;

// Picto niveau de difficulté
$diffMap  = ['Très facile' => 1, 'Very easy' => 1, 'Muy fácil' => 1,
             'Facile' => 2, 'Easy' => 2, 'Fácil' => 2,
             'Moyenne' => 3, 'Average' => 3, 'Medio' => 3];
$diffNiv  = $diffMap[$difficulte] ?? 4;
$diffPicto = $src($pix . '/picto-niv-' . $diffNiv . '.png');

// Carte statique JPG
$mapPath = Yii::getAlias(Yii::$app->params['pathCacheGmap']) . '/' . $model->id . '.jpg';
$mapSrc  = null;
if (file_exists($mapPath)) {
    $mapSrc = $forPdf
        ? 'file://' . $mapPath
        : Url::to(['/topoguide/carte', 'id' => $model->id], true);
}

// Deux premières photos
$photo1 = $photo2 = null;
foreach ([0 => 'photo1', 1 => 'photo2'] as $i => $var) {
    if (!empty($photos[$i])) {
        $p   = $photos[$i];
        $url = is_array($p) ? ($p['Photo']['Url'] ?? '') : (string)$p;
        if ($url) {
            $$var = ['url' => $url, 'titre' => is_array($p) ? ($p['Photo']['Titre'] ?? '') : ''];
        }
    }
}
if (!$photo1) {
    $defPath = $pix . '/default-1000-625.png';
    if (file_exists($defPath)) $photo1 = ['url' => $src($defPath), 'titre' => ''];
}

// Image de balisage
$balisageSrc = null;
if (!empty($balisage)) {
    $ext = strtolower(pathinfo($balisage, PATHINFO_EXTENSION));
    if (in_array($ext, ['png', 'gif', 'jpg', 'jpeg'], true)) {
        $balisageSrc = str_starts_with($balisage, 'http') ? $balisage : $cdn . '/' . $balisage;
    }
}

// Pictos SVG/PNG — pour PDF : préférer PNG (wkhtmltopdf ne rend pas les SVG) ; pour HTML : préférer SVG
$piSrc = function(string $name) use ($src, $pix, $forPdf): string {
    if ($forPdf) {
        return $src($pix . '/' . $name . '.png') ?: $src($pix . '/' . $name . '.svg');
    }
    return $src($pix . '/' . $name . '.svg') ?: $src($pix . '/' . $name . '.png');
};

$pi = [
    'where'    => $piSrc('picto-where'),
    'distance' => $piSrc('picto-distance'),
    'denivele' => $piSrc('picto-denivele'),
    'duree'    => $piSrc('picto-duree'),
    'parking'  => $piSrc('picto-parking'),
    'loop'     => $src($pix . '/picto-loop.png'),
    '112'      => $piSrc('picto-112'),
    'alerte'   => $piSrc('picto-attention'),
    'haut'     => $src($pix . '/haut_page.png'),
    'pied'     => $src($pix . '/pied_page_noir.png'),
    'typeiti'  => $src($pix . '/typeIti.png'),
];

// ── Labels multilingues ──────────────────────────────────────────────────────
[$lDepart, $lArrivee, $lDistance, $lDenivele, $lDuree,
 $lHomologue, $lItineraire, $lBoucle, $lAppel, $lBalisage, $alerteTexte]
    = match ($lang) {
        'en' => [
            'Departure:', 'Arrival:', 'Distance:', 'Elevation Gain:', 'Duration:',
            'FFRandonnée Certified', 'Itinerary:', 'Loop', 'Emergency call:', 'Signage',
            'The Basque and Béarnaise mountains are pastoral areas. Avoid going with your dog. In all cases, keep it on a leash. Thank you!',
        ],
        'es' => [
            'Salida:', 'Llegada:', 'Distancia:', 'Desnivel:', 'Duración:',
            'Homologado FFRandonnée', 'Itinerario:', 'Bucle', 'Llamada de emergencia:', 'Señalización',
            'Las montañas vascas y bearnaesas son espacios pastorales. Evite salir con su perro. En todos los casos, manténgalo con correa. ¡Gracias!',
        ],
        default => [
            'Départ :', 'Arrivée :', 'Distance :', 'Dénivelé :', 'Durée :',
            'Homologué FFRandonnée', 'Itinéraire :', 'Boucle', "Appel d'urgence :", 'Balisage',
            'Les montagnes basques et béarnaises sont des espaces pastoraux. Évitez de partir avec votre chien. Dans tous les cas, tenez-le en laisse. Merci !',
        ],
    };

$sl = match ($lang) {
    'en'    => ['poi' => "Don't miss out", 'etapes' => 'Stages', 'equip' => 'Equipment', 'warn' => 'Warning'],
    'es'    => ['poi' => 'No te lo pierdas', 'etapes' => 'Etapas', 'equip' => 'Equipamiento', 'warn' => 'Advertencias'],
    default => ['poi' => 'À ne pas manquer', 'etapes' => 'Étapes', 'equip' => 'Équipements', 'warn' => 'Attention'],
};

$randoText = match ($lang) {
    'en'    => 'To properly prepare for your hike and adopt the right behavior in the mountains, visit',
    'es'    => 'Para preparar bien tu excursión y adoptar los buenos hábitos en la montaña, visita',
    default => 'Pour bien préparer sa rando et adopter les bons gestes en montagne, rendez-vous sur',
};

$isVelo   = str_contains($type, 'élo');
$langAttr = match ($lang) { 'en' => 'en', 'es' => 'es', default => 'fr' };
?>
<!DOCTYPE html>
<html lang="<?= $langAttr ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::encode($titre) ?></title>
<style>
/* ── Reset & base ─────────────────────────────────── */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #222; background: #fff; }
img  { border: 0; }
a    { color: #1f5468; }

/* ── Skip link (RGAA) ─────────────────────────────── */
.skip-link {
    position: absolute; top: -100px; left: 0;
    background: #1f5468; color: #fff;
    padding: 8px 16px; z-index: 999;
    font-size: 10pt; text-decoration: none;
}
.skip-link:focus { top: 0; }

/* ── Navigation screen ────────────────────────────── */
.fiche-nav { background: #1f5468; padding: 8px 20px; }
.fiche-nav a { color: #fff; text-decoration: none; font-size: 10pt; margin-right: 16px; }
.fiche-nav a:hover { text-decoration: underline; }

/* ── Conteneur page ───────────────────────────────── */
.page { padding: 0 9mm; }
header{position:relative;}
/* ── Logos header ─────────────────────────────────── */
.logos-row { width: 100%; margin-bottom: 6mm; }
.logos-row td { vertical-align: middle; }
.logos-row .td-right { text-align: right; }

/* ── Titre & badges ───────────────────────────────── */
.fiche-title { font-size: 24pt; font-weight: bold; color: #000; line-height: 1.1; margin-bottom: 3mm; }
.fiche-commune { font-size: 20pt; color: #000; margin-bottom: 3mm; }
.fiche-type { display: inline-block; background: #1f5468; color: #fff; padding: 0 10px; font-size: 11pt; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4mm; font-weight:900;}
.fiche-homologue { display: inline-block; color: #E21D3B; font-weight: bold; font-size: 10pt; margin-bottom: 3mm; }
.diff-picto {position:absolute;top:40px;left:90%;z-index:100;}

/* ── Descriptif + photos ──────────────────────────── */
.desc-photo-table { width: 100%; margin-bottom: 4mm; }
.desc-photo-table td { vertical-align: top; }
.desc-cell  { width: 50%; padding-right: 4mm; }
.photo-cell { width: 50%; }
.photo-full { width: 100%; }
.photos-two-row { width: 100%; }
.photos-two-row td { width: 50%; vertical-align: top; }
.photos-two-row td:first-child { padding-right: 2mm; }
.desc-full { width: 100%; clear:both;}
img.photo { max-width: 100%; display: block; }
figcaption { font-size: 8pt; color: #666; margin-top: 1mm; }

/* ── Séparateur ───────────────────────────────────── */
.sep { border: none; border-top: 1px solid #1f5468; margin: 4mm 0; }

/* ── Tableau infos ────────────────────────────────── */
.info-row { width: 100%; margin-bottom: 2mm; }
.info-row td { vertical-align: middle; padding: 2px 4px; }
.td-picto { width: 10mm; text-align: center; }
.td-label { font-size: 11pt; color: #1f5468; font-weight: bold; white-space: nowrap; padding-right: 4px; }
.td-value { font-size: 10pt; padding-left: 4px; padding-right: 4px;  border-right: 1px solid #1f5468;}
.td-value-last { font-size: 10pt; padding-left: 4px; }

/* ── Alerte pastorale ─────────────────────────────── */
.alerte-row { width: 100%; margin: 3mm 0; }
.alerte-row td { vertical-align: middle; font-size: 11pt;font-weight:900; }
.alerte-icon { width: 10mm; text-align: center; }
.alerte-text { padding-left: 4px; }

/* ── Sections ─────────────────────────────────────── */
section { margin-top: 5mm; }
h2 { font-size: 14pt; color: #1f5468; font-weight: bold; padding-bottom: 2mm; margin-bottom: 3mm; }

/* ── POI ──────────────────────────────────────────── */
.poi-list { list-style: none; padding: 0; margin: 0; }
.poi-list li { margin-bottom: 2mm; }
.poi-nom { color: #1f5468; font-weight: bold; }

/* ── Étapes ───────────────────────────────────────── */
.etape-num { color: #1f5468; font-weight: bold; }
.etape-nom { color: #1f5468; font-weight: bold; }

/* ── Équipements / Attention ──────────────────────── */
.bullet-list { list-style: disc; padding-left: 5mm; margin: 0; }
.bullet-list li { margin-bottom: 1mm; }

/* ── Réussirmarando ───────────────────────────────── */
.rando-link { font-size: 9pt; margin-top: 4mm; }

/* ── Footer ───────────────────────────────────────── */
footer {
    margin-top: 8mm;
    font-size: 9pt;
    color: #fff;
    background-repeat: repeat-x;
    background-size: auto 100%;
    background-color: transparent;
    min-height: 18mm;
    padding: 4mm 6mm;
display:block;
text-align: center;
}
footer address { font-style: normal; }
footer a { color: #fff; }

/* ── Utilitaires ──────────────────────────────────── */
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
.no-print { }

/* ── Écran ────────────────────────────────────────── */
@media screen {
    body  { background: #f0f0f0; }
    .page { max-width: 210mm; margin: 0 auto; background: #fff; padding: 20px 9mm; box-shadow: 0 0 10px rgba(0,0,0,.15); }
}

/* ── Type itinéraire picto ────────────────────────── */
.fiche-type-wrap { display: inline-block; line-height:32px;padding:0;margin:0; float:left;}
.fiche-type-wrap img {display: inline-block; line-height:32px;padding:0;margin:0;float:left;}

/* ── Commune (picto-where) ────────────────────────── */
.picto-where { vertical-align: middle; margin-right: 4px; }

/* ── Impression / PDF ─────────────────────────────── */
@media print {
    .no-print { display: none !important; }
    .page { padding: 0 9mm; }
    @page { size: A4 portrait; margin: 0; }
    section { page-break-inside: avoid; }
    h2 { page-break-after: avoid; }
    .diff-picto { display: none !important; }
}
</style>
</head>
<body>

<a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>

<!-- Navigation (écran uniquement) -->
<nav class="fiche-nav no-print" aria-label="Navigation">
  <a href="/">Accueil</a>
  <a href="<?= Html::encode(Url::to(['/topoguide/pdf', 'lang' => $lang, 'id' => $model->id])) ?>">Télécharger le PDF</a>
  <a href="#" onclick="window.print();return false;">Imprimer</a>
</nav>

<div class="page">

  <!-- Image décorative haut de page -->


  <!-- ══════════════════════════════════════════════════
       EN-TÊTE : logos + titre + commune + type
  ═══════════════════════════════════════════════════ -->
  
  <header role="banner"<?php if (!$forPdf && $pi['haut']): ?>
    style="height:70px; background-image:url('<?= $pi['haut'] ?>'); background-repeat:repeat-x; background-size:auto 100%;"<?php endif; ?>>


    <!-- Picto difficulté (décoratif, info dans la table infos) -->
    <?php if ($diffPicto): ?>
    <img src="<?= $diffPicto ?>" alt="" class="diff-picto" aria-hidden="true" height="55">
    <?php endif; ?>
  </header>
    <!-- Titre -->
    <h1 class="fiche-title"><?= Html::encode($titre) ?></h1>

    <?php if ($homologue): ?>
    <p class="fiche-homologue"><?= Html::encode($lHomologue) ?></p>
    <?php endif; ?>

    <!-- Commune de départ -->
    <?php if ($commune): ?>
    <h2 class="fiche-commune">
      <?php if ($pi['where']): ?>
      <img src="<?= $pi['where'] ?>" alt="Commune de départ" class="picto-where" height="64">
      <?php endif; ?>
      <?= Html::encode($commune) ?>
    </h2>
    <?php endif; ?>

    <!-- Badge type itinéraire -->
    <?php if ($type): ?>
    <h3 class="fiche-type-wrap">
      <?php if ($pi['typeiti']): ?>
      <img src="<?= $pi['typeiti'] ?>" alt="" aria-hidden="true" width="16" height="32">
      <?php endif; ?>
      <span class="fiche-type" aria-label="Type d'itinéraire : <?= Html::encode($type) ?>"><?= Html::encode(mb_strtoupper($type)) ?></span>
    </h3>
    <?php endif; ?>



  <!-- ══════════════════════════════════════════════════
       CONTENU PRINCIPAL
  ═══════════════════════════════════════════════════ -->
  <main id="contenu-principal">

    <!-- Descriptif + photos ─────────────────────────── -->
    <?php if ($descriptif || $photo1): ?>
    <section aria-label="Description">
      <?php if ($photo2): ?>
        <!-- description pleine largeur, 2 photos en dessous -->
        <p class="desc-full"><?= nl2br(Html::encode($descriptif)) ?></p>
        <table class="photos-two-row" role="presentation">
          <tr>
            <td>
              <figure>
                <img src="<?= Html::encode($photo1['url']) ?>" alt="<?= Html::encode($photo1['titre'] ?: $titre) ?>" class="photo">
                <?php if ($photo1['titre']): ?><figcaption><?= Html::encode($photo1['titre']) ?></figcaption><?php endif; ?>
              </figure>
            </td>
            <td>
              <figure>
                <img src="<?= Html::encode($photo2['url']) ?>" alt="<?= Html::encode($photo2['titre'] ?: $titre) ?>" class="photo">
                <?php if ($photo2['titre']): ?><figcaption><?= Html::encode($photo2['titre']) ?></figcaption><?php endif; ?>
              </figure>
            </td>
          </tr>
        </table>
      <?php else: ?>
        <!-- descriptif gauche, 1 photo droite -->
        <table class="desc-photo-table" role="presentation">
          <tr>
            <?php if ($descriptif): ?>
            <td class="desc-cell">
              <p><?= nl2br(Html::encode($descriptif)) ?></p>
            </td>
            <?php endif; ?>
            <?php if ($photo1): ?>
            <td class="photo-cell">
              <figure>
                <img src="<?= Html::encode($photo1['url']) ?>" alt="<?= Html::encode($photo1['titre'] ?: $titre) ?>" class="photo">
                <?php if ($photo1['titre']): ?><figcaption><?= Html::encode($photo1['titre']) ?></figcaption><?php endif; ?>
              </figure>
            </td>
            <?php endif; ?>
          </tr>
        </table>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <hr class="sep" aria-hidden="true">

    <!-- Tableau d'informations ──────────────────────── -->
    <section aria-labelledby="sec-infos">
      <h2 id="sec-infos" class="sr-only">Informations pratiques</h2>

      <!-- Ligne départ / arrivée / distance / dénivelé / durée -->
      <table class="info-row" role="presentation">
        <tr>
          <?php if ($pi['where']): ?>
          <td class="td-picto"><img src="<?= $pi['where'] ?>" alt="" height="42"></td>
          <?php endif; ?>
          <th class="td-label" scope="row"><?= $lDepart ?></th>
          <td class="td-value"><?= Html::encode($commune) ?></td>

          <?php if ($pi['distance']): ?>
          <td class="td-picto"><img src="<?= $pi['distance'] ?>" alt="" height="42"></td>
          <?php endif; ?>
          <th class="td-label" scope="row"><?= $lDistance ?></th>
          <?php if ($denivele !== null && $denivele !== ''): ?>
            <td class="td-value"><?= Html::encode((string)$distance) ?> km</td>
            <?php if ($pi['denivele']): ?>
            <td class="td-picto"><img src="<?= $pi['denivele'] ?>" alt="" height="42"></td>
            <?php endif; ?>
            <th class="td-label" scope="row"><?= $lDenivele ?></th>
            <td class="td-value"><?= Html::encode((string)$denivele) ?> m</td>
          <?php else: ?>
            <td class="td-value"><?= Html::encode((string)$distance) ?> km</td>
          <?php endif; ?>

          <?php if ($duree !== ''): ?>
          <?php if ($pi['duree']): ?>
          <td class="td-picto"><img src="<?= $pi['duree'] ?>" alt="" height="42"></td>
          <?php endif; ?>
          <th class="td-label" scope="row"><?= $lDuree ?></th>
          <td class="td-value-last"><?= Html::encode($duree) ?></td>
          <?php endif; ?>
        </tr>
        <tr>
          <td class="td-picto" aria-hidden="true"></td>
          <th class="td-label" scope="row"><?= $lArrivee ?></th>
          <td class="td-value"><?= Html::encode($communeArr) ?></td>
          <td colspan="<?= ($denivele !== null && $denivele !== '') ? 5 : 3 ?>" aria-hidden="true"></td>
        </tr>
      </table>

      <!-- Ligne boucle / parking / urgence / balisage -->
      <?php $hasBoucleRow = $boucle || $parking || $balisageSrc; ?>
      <?php if ($hasBoucleRow): ?>
      <table class="info-row" role="presentation">
        <tr>
          <?php if ($boucle): ?>
          <?php if ($pi['loop']): ?>
          <td class="td-picto"><img src="<?= $pi['loop'] ?>" alt="" height="32"></td>
          <?php endif; ?>
          <th class="td-label" scope="row"><?= $lItineraire ?></th>
          <td class="td-value"><strong><?= Html::encode($lBoucle) ?></strong></td>
          <?php endif; ?>

          <?php if ($parking): ?>
          <?php if ($pi['parking']): ?>
          <td class="td-picto"><img src="<?= $pi['parking'] ?>" alt="" height="42"></td>
          <?php endif; ?>
          <td class="td-value"><?= Html::encode($parking) ?></td>
          <?php endif; ?>

          <?php if ($pi['112']): ?>
          <td class="td-picto"><img src="<?= $pi['112'] ?>" alt="" height="42"></td>
          <?php endif; ?>
          <th class="td-label" scope="row"><?= $lAppel ?></th>
          <td class="td-value"><strong>112</strong></td>

          <?php if ($balisageSrc): ?>
          <td class="td-label"><?= Html::encode($lBalisage) ?></td>
          <td class="td-value-last"><img src="<?= Html::encode($balisageSrc) ?>" alt="Balisage" height="42" class="balisage-img"></td>
          <?php endif; ?>
        </tr>
      </table>
      <?php endif; ?>

      <!-- Alerte pastorale -->
      <table class="alerte-row" role="presentation">
        <tr>
          <?php if ($pi['alerte']): ?>
          <td class="alerte-icon"><img src="<?= $pi['alerte'] ?>" alt="" height="32"></td>
          <?php endif; ?>
          <td class="alerte-text"><strong><?= Html::encode($alerteTexte) ?></strong></td>
        </tr>
      </table>
    </section>

    <hr class="sep" aria-hidden="true">

    <!-- À ne pas manquer (POI) ──────────────────────── -->
    <?php if (!empty($poi)): ?>
    <section aria-labelledby="sec-poi">
      <h2 id="sec-poi"><?= Html::encode($sl['poi']) ?></h2>
      <ul class="poi-list">
        <?php foreach ($poi as $p): ?>
          <?php
          $nom  = is_array($p) ? trim($p['nom'] ?? $p['Nom'] ?? '')               : trim((string)$p);
          $desc = is_array($p) ? trim($p['descriptif'] ?? $p['Descriptif'] ?? '') : '';
          if (!$nom && !$desc) continue;
          ?>
          <li>
            <?php if ($nom): ?><span class="poi-nom"><?= Html::encode(rtrim($nom, '.')) ?>.</span> <?php endif; ?>
            <?= Html::encode($desc) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <!-- Carte statique ──────────────────────────────── -->
    <?php if ($mapSrc): ?>
    <section aria-labelledby="sec-carte">
      <h2 id="sec-carte">Carte</h2>
      <figure>
        <img src="<?= Html::encode($mapSrc) ?>"
             alt="Carte de l'itinéraire <?= Html::encode($titre) ?>"
             style="max-width:100%;display:block;">
      </figure>
    </section>
    <?php endif; ?>

    <!-- Étapes ──────────────────────────────────────── -->
    <?php if (!empty($etapes)): ?>
    <section aria-labelledby="sec-etapes">
      <h2 id="sec-etapes"><?= Html::encode($sl['etapes']) ?></h2>
      <ol style="padding-left:5mm;margin:0;">
        <?php foreach ($etapes as $i => $e): ?>
          <?php
          if (is_array($e)) {
              $step  = (int)($e['ordre'] ?? $e['Ordre'] ?? $i + 1);
              $eNom  = (string)($e['nom_etape']  ?? $e['NomEtape']  ?? '');
              $eDesc = (string)($e['descriptif'] ?? $e['Descriptif'] ?? '');
          } else {
              $step = $i + 1; $eNom = (string)$e; $eDesc = '';
          }
          ?>
          <li value="<?= $step ?>" style="margin-bottom:2mm;">
            <?php if ($eNom): ?>
            <span class="etape-nom"><?= Html::encode(trim($eNom, '.')) ?>.</span>
            <?php endif; ?>
            <?= Html::encode($eDesc) ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
    <?php endif; ?>

    <!-- Équipements ─────────────────────────────────── -->
    <?php if (!empty($equipements)): ?>
    <section aria-labelledby="sec-equip">
      <h2 id="sec-equip"><?= Html::encode($sl['equip']) ?></h2>
      <ul class="bullet-list">
        <?php foreach ($equipements as $eq): ?>
          <?php $nom = is_array($eq) ? ($eq['nom'] ?? $eq['Nom'] ?? '') : (string)$eq;
          if (!$nom) continue; ?>
          <li><?= Html::encode($nom) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <!-- Points d'attention ──────────────────────────── -->
    <?php if (!empty($attentions)): ?>
    <section aria-labelledby="sec-attention">
      <h2 id="sec-attention"><?= Html::encode($sl['warn']) ?></h2>
      <ul class="bullet-list">
        <?php foreach ($attentions as $a): ?>
          <?php $desc = is_array($a) ? ($a['descriptif'] ?? $a['Descriptif'] ?? '') : (string)$a;
          if (!$desc) continue; ?>
          <li><?= Html::encode(trim($desc)) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <!-- Lien réussirmarando (hors vélo) ─────────────── -->
    <?php if (!$isVelo): ?>
    <p class="rando-link">
      <?= Html::encode($randoText) ?>
      <a href="https://reussirmarando.com?utm_source=pdf_iti&utm_medium=pdf&utm_campaign=pdf-iti">
        https://reussirmarando.com
      </a>
    </p>
    <?php endif; ?>

  </main>

  <!-- ══════════════════════════════════════════════════
       PIED DE PAGE : producteur
  ═══════════════════════════════════════════════════ -->
  <!-- Image décorative bas de page -->
  <?php if ($producteur && !$forPdf): ?>
  <footer role="contentinfo"<?php if ($pi['pied']): ?> style="background-image:url('<?= $pi['pied'] ?>')"<?php endif; ?>>
    <address>
      <strong><?= Html::encode($producteur->raison_sociale ?? '') ?></strong><br>
      <?php foreach (['adresse_1', 'adresse_2', 'adresse_3'] as $field): ?>
        <?php if (!empty($producteur->$field)): ?>
          <?= Html::encode($producteur->$field) ?><br>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($producteur->code_postal || $producteur->commune): ?>
        <?= Html::encode(trim($producteur->code_postal . ' ' . $producteur->commune)) ?><br>
      <?php endif; ?>
      <?php if ($producteur->telephone): ?>
        <a href="tel:<?= Html::encode($producteur->telephone) ?>"><?= Html::encode($producteur->telephone) ?></a><br>
      <?php endif; ?>
      <?php if ($producteur->url): ?>
        <a href="<?= Html::encode($producteur->url) ?>"><?= Html::encode($producteur->url) ?></a>
      <?php endif; ?>
    </address>
  </footer>
  <?php endif; ?>

</div><!-- .page -->
</body>
</html>
