<?php

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\View;
use yii\web\YiiAsset;

/**
 * Feuilles de style et scripts de la charte graphique Tourisme 64.
 * Ordre imposé : Bootstrap (dépendance), tokens.css, composants.css, puis site.css (propre à l'appli).
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/tokens.css',      // jetons et remap Bootstrap — commun, ne pas modifier
        'css/composants.css',  // composants communs — ne pas modifier
        'css/site.css',        // styles propres à l'application
    ];
    public $js = [
        'js/color-mode.js',
    ];
    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        BootstrapPluginAsset::class,
    ];
}
