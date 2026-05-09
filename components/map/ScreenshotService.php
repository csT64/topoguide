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
        $this->cachePath = Yii::getAlias(Yii::$app->params['pathCacheGmap']);
        $this->baseUrl   = rtrim(Yii::$app->params['baseUrlGmap'], '/');

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    public function captureOne(Itineraire $iti): bool
    {
        $url    = $this->buildMapUrl($iti);
        $output = $this->cachePath . '/' . $iti->id . '.jpg';

        $logFile = Yii::getAlias(Yii::$app->params['logFile']);
        $cmd = sprintf(
            'XDG_RUNTIME_DIR=/tmp/runtime-www-data DISPLAY=:10 cutycapt --url=%s --out=%s --delay=4000 --min-width=1240 --min-height=877 2>>%s',
            escapeshellarg($url),
            escapeshellarg($output),
            escapeshellarg($logFile)
        );

        exec($cmd, $out, $code);

        if ($code !== 0 || !file_exists($output)) {
            $msg = date('Y-m-d H:i:s') . " [screenshot] FAIL code=$code url=$url\n";
            @file_put_contents($logFile, $msg, FILE_APPEND);
            return false;
        }

        return true;
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
