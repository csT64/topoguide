<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use app\components\cache\TopoguideCacheService;
use app\models\Itineraire;

class CacheController extends Controller
{

    public function actionIndex(): string
    {
        $cache = new TopoguideCacheService();
        $stats = $cache->countCachedFiles();
        return $this->render('index', ['stats' => $stats]);
    }

    public function actionPurgeInactive(): \yii\web\Response
    {
        $cache = new TopoguideCacheService();
        $count = 0;
        foreach (['fr', 'en', 'es'] as $lang) {
            Yii::$app->language = $lang;
            $ids = Itineraire::find()
                ->where(['or', ['is_active' => 0], ['not', ['deleted_at' => null]]])
                ->select('syndic_object_id')
                ->column();
            foreach ($ids as $sid) {
                if ($sid) {
                    $cache->purge($lang, $sid);
                    $count++;
                }
            }
        }
        Yii::$app->session->setFlash('success', "$count entrées de cache purgées (fiches inactives/supprimées).");
        return $this->redirect(['index']);
    }

    public function actionPurgeAll(): \yii\web\Response
    {
        $cache = new TopoguideCacheService();
        $count = 0;
        foreach (['fr', 'en', 'es'] as $lang) {
            $dir = $cache->getDir($lang);
            foreach (glob($dir . '/*.meta') ?: [] as $f) {
                $syndicId = basename($f, '.meta');
                $cache->purge($lang, $syndicId);
                $count++;
            }
        }
        Yii::$app->session->setFlash('success', "Cache entièrement vidé ($count entrées supprimées).");
        return $this->redirect(['index']);
    }

    public function actionPurgeFiche(string $syndicId): \yii\web\Response
    {
        $cache = new TopoguideCacheService();
        $cache->purgeAllLangs($syndicId);
        Yii::$app->session->setFlash('success', "Cache purgé pour $syndicId (toutes langues).");
        return $this->redirect(['index']);
    }
}
