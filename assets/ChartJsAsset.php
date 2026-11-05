<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Chart.js — utilisé par la page spécimen de la charte graphique.
 */
class ChartJsAsset extends AssetBundle
{
    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js',
    ];
    public $depends = [AppAsset::class];
}
