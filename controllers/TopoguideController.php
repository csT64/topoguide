<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;
use app\components\pdf\TopoguideServiceWeasy;
use app\components\pdf\TopoguideServicePrince;

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
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;

        return $this->render('fiche', ['model' => $iti, 'lang' => $lang, 'forPdf' => false, 'modelFr' => $itiFr]);
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
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;

        $engine = Yii::$app->params['pdfEngine'] ?? 'weasyprint';
        $svc = $engine === 'prince'
            ? new TopoguideServicePrince($iti, $lang, $itiFr)
            : new TopoguideServiceWeasy($iti, $lang, $itiFr);
        $svc->generate();
    }

    public function actionPdfWeasy(string $lang, string $id): void
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;
        (new TopoguideServiceWeasy($iti, $lang, $itiFr))->generate();
    }

    public function actionPdfPrince(string $lang, string $id): void
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;
        (new TopoguideServicePrince($iti, $lang, $itiFr))->generate();
    }

    // ── Debug HTML (visualisation avant génération PDF) ──────────────────────────
    // URLs : /topoguide/fr/ID.weasy  |  /topoguide/fr/ID.prince-html

    public function actionWeasyDebug(string $lang, string $id): string
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;
        $svc   = new TopoguideServiceWeasy($iti, $lang, $itiFr);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $svc->renderHtml(false);
    }

    public function actionPrinceDebug(string $lang, string $id): string
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti   = $this->loadItineraire($id);
        $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;
        $svc   = new TopoguideServicePrince($iti, $lang, $itiFr);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $svc->renderHtml(false);
    }


}
