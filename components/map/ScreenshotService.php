<?php

namespace app\components\map;

use Yii;
use app\models\Itineraire;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

class ScreenshotService
{
    private string $cachePath;
    private string $baseUrl;
    private string $logFile;

    public function __construct()
    {
        $this->cachePath = Yii::getAlias(Yii::$app->params['pathCacheGmap']);
        $this->baseUrl   = rtrim(Yii::$app->params['baseUrlGmap'], '/');
        $this->logFile   = Yii::getAlias(Yii::$app->params['logFile']);

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    public function captureOne(Itineraire $iti): bool
    {
        $url    = $this->buildMapUrl($iti);
        $output = $this->cachePath . '/' . $iti->id . '.jpg';

        try {
            $factory = new BrowserFactory('chromium');
            $browser = $factory->createBrowser([
                'noSandbox'        => true,
                'windowSize'       => [1240, 877],
                'userDataDir'      => '/tmp/chrome-topoguide',
                'additionalArguments' => [
                    '--disable-dev-shm-usage',
                    '--ignore-certificate-errors',
                ],
            ]);

            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation(Page::NETWORK_IDLE, 15000);

            // Laisser le temps aux tuiles OSM de se charger complètement
            $page->evaluate("new Promise(r => setTimeout(r, 5000))")->getReturnValue();

            $page->screenshot([
                'format'  => 'jpeg',
                'quality' => 85,
                'clip'    => new Clip(0, 0, 1240, 877),
            ])->saveToFile($output);

            $browser->close();

            return file_exists($output);

        } catch (\Exception $e) {
            $msg = date('Y-m-d H:i:s') . " [screenshot] FAIL url=$url error=" . $e->getMessage() . "\n";
            @file_put_contents($this->logFile, $msg, FILE_APPEND);
            return false;
        }
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
