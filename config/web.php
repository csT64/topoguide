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
            'cookieValidationKey' => '',  // définie dans config/web-local.php — ne pas committer
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
                // Fiche HTML publique
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>' => 'topoguide/view',

                // Carte statique JPG
                'topoguide/carte/<id:[A-Z0-9]+>' => 'topoguide/carte',

                // PDF public
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf' => 'topoguide/pdf',

                // Debug PDF — visualise les HTML intermédiaires dans le navigateur
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf-<part:(header|footer|content)>' => 'topoguide/pdf-debug',

                // Cartes Leaflet interactives
                'gmap/simple' => 'gmap/simple',
                'gmap/gpx'    => 'gmap/gpx',
                'gmap/kml'    => 'gmap/kml',
                'gmap/proxy'  => 'gmap/proxy',

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
