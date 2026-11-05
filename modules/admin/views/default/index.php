<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $stats */
/** @var int $nbProducteurs */
/** @var int $manquantes */
/** @var array $logLines */

$this->title = 'Tableau de bord';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="default-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
    </div>

    <h2 class="visually-hidden">Chiffres clés</h2>
    <div class="stat-cards mb-4">
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Itinéraires FR</h3>
            <p class="kpi"><?= $stats['fr'] ?></p>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Itinéraires EN</h3>
            <p class="kpi"><?= $stats['en'] ?></p>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('files', ['class' => 'ic-lg']) ?></div>
            <h3>Itinéraires ES</h3>
            <p class="kpi"><?= $stats['es'] ?></p>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon"><?= Icon::svg('building-community', ['class' => 'ic-lg']) ?></div>
            <h3>Producteurs</h3>
            <p class="kpi"><?= $nbProducteurs ?></p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="panel <?= $manquantes > 0 ? 'border-warning' : '' ?>">
                <h2 class="section-title"><?= Icon::svg('chart-bar') ?> Captures carte</h2>
                <p class="kpi mb-2"><?= $manquantes ?></p>
                <p class="text-body-secondary">capture(s) manquante(s)</p>
                <?php if ($manquantes > 0): ?>
                    <?= Bouton::post(
                        Icon::svg('refresh') . ' Générer les captures manquantes',
                        Url::to(['/admin/carte/generer-manquantes']),
                        ['class' => 'btn btn-warning']
                    ) ?>
                <?php else: ?>
                    <p class="mb-0"><span class="pill pill-ok"><span class="pill-dot"></span>Toutes les captures sont présentes</span></p>
                <?php endif ?>
            </section>
        </div>
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">
                    <?= Icon::svg('history') ?> Dernières erreurs
                    <?= Html::a('Voir tout', ['/admin/log/index'], ['class' => 'btn btn-sm btn-outline-secondary ms-2']) ?>
                </h2>
                <?php if (empty($logLines)): ?>
                    <p class="mb-0 text-body-secondary">Aucune erreur.</p>
                <?php else: ?>
                    <pre style="max-height:200px;overflow:auto;font-size:12px;background:var(--color-surface);color:var(--color-ink);padding:10px;border-radius:4px;margin:0"><?= Html::encode(implode("\n", $logLines)) ?></pre>
                <?php endif ?>
            </section>
        </div>
    </div>
</div>
