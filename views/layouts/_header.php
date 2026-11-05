<?php

/**
 * Barre de navigation — Topoguide admin.
 *
 * @var yii\web\View $this
 */

use app\helpers\Bouton;
use app\helpers\Icon;
use app\helpers\Marque;
use app\helpers\Menu;
use app\widgets\NavBar;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;

$connecte = !Yii::$app->user->isGuest;

$items = [
    [
        'label'   => Icon::svg('layout-dashboard') . ' Tableau de bord',
        'url'     => ['/admin/default/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('files') . ' Itinéraires',
        'url'     => ['/admin/itineraire/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('building-community') . ' Producteurs',
        'url'     => ['/admin/producteur/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('map-pin') . ' Villes',
        'url'     => ['/admin/ville/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('chart-bar') . ' Cartes',
        'url'     => ['/admin/carte/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('list-check') . ' Qualité',
        'url'     => ['/admin/qualite/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('database') . ' Cache',
        'url'     => ['/admin/cache/index'],
        'visible' => $connecte,
    ],
    [
        'label'   => Icon::svg('history') . ' Logs',
        'url'     => ['/admin/log/index'],
        'visible' => $connecte,
    ],
    $connecte ? Menu::deroulant(Icon::svg('settings') . ' Administration', [
        ['label' => Icon::svg('building-store') . ' TourInSoft', 'url' => ['/tourinsoft-client/dashboard/index']],
        ['label' => Icon::svg('award') . ' Charte graphique', 'url' => ['/charte/index']],
    ]) : '',
];

$marque = Marque::existe(Marque::NEGATIF)
    ? Marque::logo(Marque::NEGATIF, ['class' => 'logo-marque me-2']) . '<span class="visually-hidden"> — </span>' . Html::encode(Yii::$app->name)
    : Icon::svg('award') . ' ' . Html::encode(Yii::$app->name);
?>
<header id="header">
    <?php NavBar::begin([
        'brandLabel' => $marque,
        'brandUrl' => Yii::$app->homeUrl,
        'innerContainerOptions' => ['class' => 'container-fluid px-3 px-xl-4'],
        'options' => ['class' => 'navbar-expand-xl navbar-dark bg-dark fixed-top'],
    ]) ?>
    <?= Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto'],
        'encodeLabels' => false,
        'items' => $items,
    ]) ?>
    <?php if ($connecte): ?>
        <?= Bouton::post(
            Icon::svg('logout') . ' <span class="visually-hidden">Se déconnecter — </span>' . Html::encode(Yii::$app->user->identity->username),
            ['/admin/default/logout'],
            ['class' => 'btn btn-link nav-link logout', 'title' => 'Se déconnecter']
        ) ?>
    <?php endif ?>
    <?= Html::button(
        Icon::svg('moon', ['class' => 'icone-theme-clair']) . Icon::svg('sun', ['class' => 'icone-theme-sombre'])
            . '<span class="visually-hidden" id="theme-toggle-texte">Passer en mode sombre</span>',
        ['id' => 'theme-toggle', 'type' => 'button', 'class' => 'btn btn-link nav-link fs-5 ms-xl-2', 'title' => 'Passer en mode sombre']
    ) ?>
    <?php NavBar::end() ?>
</header>
