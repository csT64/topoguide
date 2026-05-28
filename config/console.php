<?php

$params      = require __DIR__ . '/params.php';
$db          = require __DIR__ . '/db.php';
$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

$config = [
    'id'                   => 'topoguide-console',
    'basePath'             => dirname(__DIR__),
    'bootstrap'            => ['log', 'tourinsoftclient\components\TourinsoftClientBootstrap'],
    'controllerNamespace'  => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'queue' => [
            'class'     => 'yii\queue\db\Queue',
            'db'        => 'db',
            'tableName' => '{{%queue}}',
            'channel'   => 'default',
            'mutex'     => 'yii\mutex\MysqlMutex',
        ],
        'log' => [
            'targets' => [
                [
                    'class'   => 'yii\log\FileTarget',
                    'levels'  => ['error', 'warning'],
                    'logFile' => '@runtime/logs/console.log',
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
    ],
    'modules' => [
        'tourinsoft-client' => [
            'class'             => 'tourinsoftclient\Module',
            'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
            'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
        ],
    ],
    'params' => $params,
];

return $config;
