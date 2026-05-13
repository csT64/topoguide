<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;
use app\components\pdf\TopoguideService;
use app\components\pdf\TopoguideServiceWeasy;

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

        $engine = Yii::$app->params['pdfEngine'] ?? 'wkhtmltopdf';
        $svc = $engine === 'weasyprint'
            ? new TopoguideServiceWeasy($iti, $lang)
            : new TopoguideService($iti, $lang);
        $svc->generate();
    }

    // ── Debug WeasyPrint (HTML avec CSS paged media, pour test CLI) ──────────────
    // URL : /topoguide/fr/ID.weasy
    // Usage : weasyprint http://topoguide.local/topoguide/fr/ID.weasy output.pdf

    public function actionWeasyDebug(string $lang, string $id): string
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti = $this->loadItineraire($id);
        $svc = new TopoguideServiceWeasy($iti, $lang);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $svc->renderHtml(true);
    }

    // ── Debug PDF (visualisation des HTML intermédiaires) ─────────────────────
    // URLs : /topoguide/fr/ID.pdf-header | .pdf-footer | .pdf-content

    public function actionPdfDebug(string $lang, string $id, string $part = 'content'): string
    {
        $this->validateLang($lang);
        Yii::$app->language = $lang;
        $iti  = $this->loadItineraire($id);
        $svc  = new TopoguideService($iti, $lang);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return match ($part) {
            'header'  => $svc->debugHeaderHtml(),
            'footer'  => $svc->debugFooterHtml(),
            default   => $svc->renderHtml(true),
        };
    }
}
