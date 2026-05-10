<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;
use app\components\pdf\TopoguideService;

class TopoguideController extends Controller
{
    public $layout = false;

    private function loadItineraire(string $id): Itineraire
    {
        $iti = Itineraire::findOne(['id' => $id]);
        if (!$iti) {
            throw new NotFoundHttpException("Itinéraire $id introuvable.");
        }
        return $iti;
    }

    private function validateLang(string $lang): void
    {
        if (!in_array($lang, ['fr', 'en', 'es'], true)) {
            throw new NotFoundHttpException("Langue invalide : $lang");
        }
    }

    // ── Page HTML publique ────────────────────────────────────────────────────

    public function actionView(string $lang, string $id): string
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti = $this->loadItineraire($id);

        return $this->render('fiche', ['model' => $iti, 'lang' => $lang, 'forPdf' => false]);
    }

    // ── Carte statique JPG ────────────────────────────────────────────────────

    public function actionCarte(string $id): void
    {
        $path = Yii::getAlias(Yii::$app->params['pathCacheGmap']) . '/' . $id . '.jpg';
        if (!file_exists($path)) {
            throw new NotFoundHttpException("Carte $id introuvable.");
        }
        Yii::$app->response->sendFile($path, $id . '.jpg', ['mimeType' => 'image/jpeg', 'inline' => true]);
    }

    // ── Génération PDF ────────────────────────────────────────────────────────

    public function actionPdf(string $lang, string $id): void
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti = $this->loadItineraire($id);

        (new TopoguideService($iti, $lang))->generate();
    }
}
