<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class LogController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function actionIndex(): string
    {
        $logFile = Yii::getAlias(Yii::$app->params['logFile']);
        $lines   = [];

        if (file_exists($logFile)) {
            $raw   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse(array_slice($raw, -500));
        }

        return $this->render('index', ['lines' => $lines, 'logFile' => $logFile]);
    }

    public function actionVider(): Response
    {
        $logFile = Yii::getAlias(Yii::$app->params['logFile']);
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        Yii::$app->session->addFlash('success', 'Log vidé.');
        return $this->redirect(['index']);
    }
}
