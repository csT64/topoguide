<?php

namespace app\modules\admin;

use yii\filters\AccessControl;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\admin\controllers';
    public $defaultRoute        = 'default';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function () {
                    return \Yii::$app->response->redirect(['/admin/default/login']);
                },
            ],
        ];
    }
}
