<?php

namespace app\modules\admin;

use Yii;
use yii\filters\AccessControl;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\admin\controllers';
    public $defaultRoute        = 'default';
    public $layout              = '@app/views/layouts/main';

    public function init(): void
    {
        parent::init();
        Yii::$app->name    = 'Topoguide : gestion des fiches mémo-rando';
        Yii::$app->homeUrl = \yii\helpers\Url::to(['/admin/default/index']);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['login'],
                        'roles'   => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function () {
                    return Yii::$app->response->redirect(['/admin/default/login']);
                },
            ],
        ];
    }
}
