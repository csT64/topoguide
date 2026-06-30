<?php

// Surcharge par serveur (non versionnée) : return ['debug' => true, 'env' => 'dev'];
// Par défaut (fichier absent) : environnement de production sécurisé.
$envLocal = __DIR__ . '/../config/env-local.php';
$envOverride = file_exists($envLocal) ? require $envLocal : [];

defined('YII_DEBUG') or define('YII_DEBUG', $envOverride['debug'] ?? false);
defined('YII_ENV')   or define('YII_ENV', $envOverride['env'] ?? 'prod');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
