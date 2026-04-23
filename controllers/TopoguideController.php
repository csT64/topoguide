<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Itineraire;
use app\components\pdf\TopoguideService;

class TopoguideController extends Controller
{
    public $layout = false;

    public function actionPdf(string $lang, string $id): void
    {
        if (!in_array($lang, ['fr', 'en', 'es'])) {
            throw new NotFoundHttpException("Langue invalide : $lang");
        }

        Yii::$app->language = $lang;
        $iti = Itineraire::findOne(['id' => $id]);

        if (!$iti) {
            throw new NotFoundHttpException("Itinéraire $id introuvable.");
        }

        (new TopoguideService($iti, $lang))->generate();
    }
}
