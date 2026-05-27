<?php

$params      = require __DIR__ . '/params.php';
$db          = require __DIR__ . '/db.php';
$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

$config = [
    'id'       => 'topoguide',
    'name'     => 'Topoguide',
    'basePath' => dirname(__DIR__),
    'language' => 'fr',
    'bootstrap' => ['log', 'tourinsoftclient\components\TourinsoftClientBootstrap'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => '',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass'   => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl'        => ['/admin/default/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'queue' => [
            'class'     => 'yii\queue\db\Queue',
            'db'        => 'db',
            'tableName' => '{{%queue}}',
            'channel'   => 'default',
            'mutex'     => 'yii\mutex\MysqlMutex',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                ],
                [
                    'class'       => 'yii\log\FileTarget',
                    'levels'      => ['error', 'warning', 'info'],
                    'categories'  => ['extraction.*'],
                    'logFile'     => '@runtime/logs/extraction.log',
                    'maxFileSize' => 2048,
                    'maxLogFiles' => 5,
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

                // PDF public (moteur selon params.php)
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf' => 'topoguide/pdf',

                // PDF explicitement par moteur
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf-wk'     => 'topoguide/pdf-wk',
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf-weasy'  => 'topoguide/pdf-weasy',
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf-prince' => 'topoguide/pdf-prince',

                // Debug HTML
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf-<part:(header|footer|content)>' => 'topoguide/pdf-debug',
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.weasy'       => 'topoguide/weasy-debug',
                'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.prince-html' => 'topoguide/prince-debug',

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
        'tourinsoft-client' => [
            'class'             => 'tourinsoftclient\Module',
            'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
            'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
        ],
    ],
    'params' => $params,
];

// Surcharge locale (cookieValidationKey, options propres au serveur) — non versionné
if (file_exists(__DIR__ . '/web-local.php')) {
    $config = \yii\helpers\ArrayHelper::merge($config, require __DIR__ . '/web-local.php');
}

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
