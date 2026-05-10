<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;

class GmapController extends Controller
{
    public $layout = false;

    private const ALLOWED_HOSTS = [
        'cdt64.media.tourinsoft.eu',
        'api.tourisme64.com',
        'api.adt64.fr',
    ];

    public function actionSimple(float $lat, float $lon, int $zoom = 12): string
    {
        return $this->render('simple', compact('lat', 'lon', 'zoom'));
    }

    public function actionGpx(string $file, array $marker = []): string
    {
        return $this->render('gpx', compact('file', 'marker'));
    }

    public function actionKml(string $file, array $marker = []): string
    {
        return $this->render('kml', compact('file', 'marker'));
    }

    // Proxy pour contourner les restrictions CORS sur les fichiers GPX/KML du CDN
    public function actionProxy(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new BadRequestHttpException("Hôte non autorisé : $host");
        }

        $ctx  = stream_context_create(['http' => [
            'timeout' => 15,
            'header'  => "User-Agent: topoguide-map/1.0\r\n",
        ]]);
        $data = @file_get_contents($url, false, $ctx);

        if ($data === false) {
            throw new \yii\web\ServerErrorHttpException("Impossible de récupérer : $url");
        }

        $ext  = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mime = match($ext) {
            'gpx'  => 'application/gpx+xml',
            'kml'  => 'application/vnd.google-earth.kml+xml',
            'kmz'  => 'application/vnd.google-earth.kmz',
            default => 'application/xml',
        };

        Yii::$app->response->headers->set('Content-Type', $mime);
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=3600');
        Yii::$app->response->content = $data;
        Yii::$app->response->send();
        exit;
    }
}
