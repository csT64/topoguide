<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Itineraire;
use app\components\map\StaticMapService;

class ScreenshotController extends Controller
{
    public function actionRun(): int
    {
        Yii::$app->language = 'fr';
        $service   = new StaticMapService();
        $cachePath = Yii::getAlias(Yii::$app->params['pathCacheGmap']);

        $ids = Itineraire::find()->select('id')->column();
        $nb  = 0;

        foreach ($ids as $id) {
            $jpgPath = "$cachePath/$id.jpg";
            $needCapture = !file_exists($jpgPath);

            if (!$needCapture) {
                // Recapturer si l'itinéraire a été modifié depuis la dernière capture
                $iti = Itineraire::findOne(['id' => $id]);
                if ($iti && $iti->updated_at) {
                    $modif    = strtotime($iti->updated_at);
                    $capture  = filemtime($jpgPath);
                    $needCapture = $modif > $capture;
                }
            }

            if ($needCapture) {
                $iti = $iti ?? Itineraire::findOne(['id' => $id]);
                if (!$iti) continue;

                $this->stdout("Capture : $id ... ");
                $ok = $service->generate($iti);
                $this->stdout($ok ? "OK\n" : "ÉCHEC\n");

                if ($ok) $nb++;
                sleep(2); // délai entre captures
            }
        }

        $this->stdout("$nb capture(s) générée(s).\n");
        return ExitCode::OK;
    }

    public function actionOne(string $id): int
    {
        Yii::$app->language = 'fr';
        $iti = Itineraire::findOne(['id' => $id]);

        if (!$iti) {
            $this->stderr("Itinéraire $id introuvable.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $ok = (new StaticMapService())->generate($iti);
        $this->stdout($ok ? "Capture $id générée.\n" : "Échec de la capture $id.\n");
        return $ok ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    public function actionDebug(string $id): int
    {
        Yii::$app->language = 'fr';
        $iti = Itineraire::findOne(['id' => $id]);
        if (!$iti) {
            $this->stderr("Itinéraire $id introuvable.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("doc_gpx  : " . ($iti->doc_gpx ?: '(vide)') . "\n");
        $this->stdout("doc_kml  : " . ($iti->doc_kml ?: '(vide)') . "\n");
        $this->stdout("lat/lon  : {$iti->latitude} / {$iti->longitude}\n");

        $etapes = $iti->getEtapes();
        $this->stdout("étapes   : " . count($etapes) . " entrée(s)\n");
        foreach ($etapes as $i => $e) {
            $lat = $e['latitudedecimalegooglemap']  ?? $e['lat'] ?? '?';
            $lon = $e['longitudedecimalegooglemap'] ?? $e['lon'] ?? '?';
            $nom = $e['nom_etape'] ?? $e['nom'] ?? '?';
            $this->stdout("  [$i] $nom — lat=$lat lon=$lon\n");
        }

        // Test parsing GPX
        if ($iti->doc_gpx) {
            $ctx  = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: topoguide-map/1.0\r\n"]]);
            $data = @file_get_contents($iti->doc_gpx, false, $ctx);
            if (!$data) {
                $this->stdout("GPX      : IMPOSSIBLE DE TÉLÉCHARGER\n");
            } else {
                $xml = simplexml_load_string($data);
                $ns  = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('g', $ns[''] ?? 'http://www.topografix.com/GPX/1/1');
                $pts = $xml->xpath('//g:trkpt');
                $this->stdout("GPX      : " . count($pts) . " points trkpt\n");
                if (count($pts) > 0) {
                    $this->stdout("  premier: lat={$pts[0]['lat']} lon={$pts[0]['lon']}\n");
                    $this->stdout("  dernier: lat={$pts[count($pts)-1]['lat']} lon={$pts[count($pts)-1]['lon']}\n");
                }
            }
        }

        // Test GD
        $this->stdout("GD       : " . (function_exists('imagecreatetruecolor') ? 'OK' : 'ABSENT') . "\n");
        $this->stdout("FreeType : " . (function_exists('imagettftext') ? 'OK' : 'ABSENT') . "\n");

        return ExitCode::OK;
    }
}
