<?php

namespace app\components\map;

use Yii;
use app\models\Itineraire;

class ScreenshotService
{
    private string $cachePath;
    private string $baseUrl;

    public function __construct()
    {
        $this->cachePath = Yii::$app->params['pathCacheGmap'];
        $this->baseUrl   = rtrim(Yii::$app->params['baseUrlGmap'], '/');
    }

    public function captureOne(Itineraire $iti): bool
    {
        $url    = $this->buildMapUrl($iti);
        $output = $this->cachePath . '/' . $iti->id . '.jpg';

        $cmd = sprintf(
            'xvfb-run --server-args="-screen 0, 1240x877x24" '
            . 'cutycapt --url=%s --out=%s --delay=1000 2>/dev/null',
            escapeshellarg($url),
            escapeshellarg($output)
        );

        exec($cmd, $out, $code);
        return $code === 0 && file_exists($output);
    }

    private function buildMapUrl(Itineraire $iti): string
    {
        $etapes  = $iti->getEtapes();
        $markers = [];

        if ($iti->latitude && $iti->longitude) {
            $markers[] = 'Départ!1!' . $iti->latitude . '!' . $iti->longitude;
        }

        foreach ($etapes as $i => $etape) {
            if (!empty($etape['lat']) && !empty($etape['lon'])) {
                $nom       = $etape['nom'] ?? 'Étape ' . ($i + 1);
                $markers[] = $nom . '!' . ($i + 2) . '!' . $etape['lat'] . '!' . $etape['lon'];
            }
        }

        if ($iti->doc_gpx) {
            $route = 'gmap/gpx?file=' . urlencode($iti->doc_gpx);
        } elseif ($iti->doc_kml) {
            $route = 'gmap/kml?file=' . urlencode($iti->doc_kml);
        } else {
            $zoom  = 13;
            $route = 'gmap/simple?lat=' . $iti->latitude . '&lon=' . $iti->longitude . '&zoom=' . $zoom;
        }

        $url = $this->baseUrl . '/' . $route;
        foreach ($markers as $m) {
            $url .= '&marker[]=' . urlencode($m);
        }

        return $url;
    }
}
