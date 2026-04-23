<?php

$params = require __DIR__ . '/params.php';
$db     = require __DIR__ . '/db.php';

$config = [
    'id'       => 'topoguide',
    'name'     => 'Topoguide',
    'basePath' => dirname(__DIR__),
    'language' => 'fr',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => '',  // généré automatiquement à l'installation
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass'  => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl'        => ['/admin/default/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules' => [
                // PDF public
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf' => 'topoguide/pdf',

                // Cartes Leaflet (appelées par CutyCapt)
                'gmap/simple' => 'gmap/simple',
                'gmap/gpx'    => 'gmap/gpx',
                'gmap/kml'    => 'gmap/kml',

                // Admin
                'admin/login'                                  => 'admin/default/login',
                'admin/logout'                                 => 'admin/default/logout',
                'admin'                                        => 'admin/default/index',
                'admin/<controller:[a-z-]+>'                   => 'admin/<controller>/index',
                'admin/<controller:[a-z-]+>/<action:[a-z-]+>'  => 'admin/<controller>/<action>',
            ],
        ],
    ],
    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\Module',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
