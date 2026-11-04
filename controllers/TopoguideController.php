<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;
use app\components\pdf\TopoguideServiceWeasy;
use app\components\pdf\TopoguideServicePrince;
use app\components\cache\TopoguideCacheService;
use app\components\map\StaticMapService;

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

    // ── Lazy cache : PDF et HTML accessibles ─────────────────────────────────
    // GET /topoguide/{lang}/{id}.pdf   → format=pdf
    // GET /topoguide/{lang}/{id}.html  → format=html

    public function actionServe(string $lang, string $id, string $format = 'pdf'): void
    {
        $this->validateLang($lang);
        if (!in_array($format, ['pdf', 'html'], true)) {
            throw new NotFoundHttpException("Format invalide : $format");
        }

        Yii::$app->language = $lang;
        $iti   = $this->loadItineraire($id);
        $cache = new TopoguideCacheService();

        // Identifiant stable TourInSoft pour nommer les fichiers cache
        $cacheId = $iti->syndic_object_id ?: $iti->id;

        // Vérification 1 — fiche inactive ou supprimée (indépendant de updated_at)
        if (!$iti->is_active || $iti->deleted_at !== null) {
            $cache->purge($lang, $cacheId);
            throw new NotFoundHttpException("Cette fiche n'est plus disponible.");
        }

        // Vérification 2 — fraîcheur du cache (indépendante de is_active/deleted_at)
        $meta       = $cache->readMeta($lang, $cacheId);
        $needsRegen = ($meta === null)
            || !$cache->hasFile($lang, $cacheId, $format)
            || $cache->isCacheStale($meta, $iti->updated_at);

        if ($needsRegen) {
            $itiFr = $lang !== 'fr' ? Itineraire::findFr($id) : null;

            // Carte statique requise pour le PDF
            if (!$iti->hasCarteCache()) {
                (new StaticMapService())->generate($iti);
            }

            $engine = Yii::$app->params['pdfEngine'] ?? 'weasyprint';
            $svc = $engine === 'prince'
                ? new TopoguideServicePrince($iti, $lang, $itiFr)
                : new TopoguideServiceWeasy($iti, $lang, $itiFr);

            if ($format === 'pdf') {
                $content = $svc->generateToString();
            } else {
                $content = $svc->renderHtml(false);
            }

            $cache->writeFile($lang, $cacheId, $format, $content);
            // Méta commune aux deux formats — écrite après chaque regénération
            $cache->writeMeta($lang, $cacheId, $iti->updated_at ?? date('Y-m-d H:i:s'));
        }

        $content = $cache->readFile($lang, $cacheId, $format);

        Yii::$app->response->format = Response::FORMAT_RAW;

        if ($format === 'pdf') {
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $iti->getTitle());
            Yii::$app->response->headers->set('Content-Type', 'application/pdf');
            Yii::$app->response->headers->set('Content-Disposition', 'inline; filename="topoguide_' . $slug . '.pdf"');
        } else {
            Yii::$app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
        }

        Yii::$app->response->headers->set('Content-Length', (string)strlen($content));
        Yii::$app->response->content = $content;
        Yii::$app->response->send();
        Yii::$app->end();
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
