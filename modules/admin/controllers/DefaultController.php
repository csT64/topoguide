<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\Itineraire;
use app\models\Producteur;
use app\models\LoginForm;

class DefaultController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => ['logout' => ['post']],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        // Login/logout sont accessibles sans authentification
        if (in_array($action->id, ['login', 'logout'])) {
            $this->layout = false;
        }
        return parent::beforeAction($action);
    }

    public function actionIndex(): string
    {
        $stats = [];
        foreach (['fr', 'en', 'es'] as $lang) {
            Yii::$app->language = $lang;
            $stats[$lang] = Itineraire::find()->count();
        }
        Yii::$app->language = 'fr';

        $nbProducteurs = Producteur::find()->count();

        $cachePath = Yii::$app->params['pathCacheGmap'];
        $manquantes = 0;
        foreach (Itineraire::find()->select('id')->column() as $id) {
            if (!file_exists("$cachePath/$id.jpg")) {
                $manquantes++;
            }
        }

        $logFile  = Yii::getAlias(Yii::$app->params['logFile']);
        $logLines = [];
        if (file_exists($logFile)) {
            $raw      = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse(array_slice($raw, -20));
        }

        return $this->render('index', compact('stats', 'nbProducteurs', 'manquantes', 'logLines'));
    }

    public function actionLogin(): \yii\web\Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['index']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['index']);
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout(): \yii\web\Response
    {
        Yii::$app->user->logout();
        return $this->redirect(['login']);
    }
}
