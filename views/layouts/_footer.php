<?php

/**
 * Pied de page — charte graphique Tourisme 64.
 * Logo couleur en thème clair, blanc en thème sombre (composants.css), s'ils sont fournis.
 *
 * @var yii\web\View $this
 */

use app\helpers\Marque;
use yii\bootstrap5\Html;

$logos = Marque::logo(Marque::POSITIF, ['class' => 'logo-pied logo-pied-positif'])
    . Marque::logo(Marque::NEGATIF, ['class' => 'logo-pied logo-pied-negatif']);
?>
<footer id="footer" class="mt-auto py-3 bg-body-tertiary">
    <div class="container">
        <div class="row align-items-center text-body-secondary small gy-2">
            <div class="col-md-6 text-center text-md-start">
                <?= $logos ? $logos . ' ' : '' ?>&copy; <?= Html::encode(Yii::$app->name) ?> — Tourisme 64, <?= date('Y') ?>
            </div>
            <div class="col-md-6 text-center text-md-end">Données Tourinsoft</div>
        </div>
    </div>
</footer>
