<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Itineraire;
use app\components\map\ScreenshotService;

class ScreenshotController extends Controller
{
    public function actionRun(): int
    {
        Yii::$app->language = 'fr';
        $service   = new ScreenshotService();
        $cachePath = Yii::$app->params['pathCacheGmap'];

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
                $ok = $service->captureOne($iti);
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

        $ok = (new ScreenshotService())->captureOne($iti);
        $this->stdout($ok ? "Capture $id générée.\n" : "Échec de la capture $id.\n");
        return $ok ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
