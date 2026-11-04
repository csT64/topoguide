<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\components\cache\TopoguideCacheService;
use app\models\Itineraire;

class TopoguideController extends Controller
{
    public $defaultAction = 'cache-stats';

    public function actionCacheStats(): int
    {
        $cache = new TopoguideCacheService();
        $stats = $cache->countCachedFiles();
        foreach ($stats as $lang => $counts) {
            $this->stdout(sprintf("%-4s  PDF: %4d  HTML: %4d\n", $lang, $counts['pdf'], $counts['html']));
        }
        return ExitCode::OK;
    }

    /**
     * Purge le cache d'une fiche ou de toutes les fiches inactives/supprimées.
     *
     * @param string|null $syndicId  syndic_object_id à purger (null = toutes les inactives)
     * @param string      $lang      langue cible ou 'all' (défaut)
     */
    public function actionInvalidateCache(?string $syndicId = null, string $lang = 'all'): int
    {
        $cache = new TopoguideCacheService();

        if ($syndicId !== null) {
            if ($lang === 'all') {
                $cache->purgeAllLangs($syndicId);
                $this->stdout("Cache purgé : $syndicId (toutes langues)\n");
            } else {
                $cache->purge($lang, $syndicId);
                $this->stdout("Cache purgé : $syndicId / $lang\n");
            }
            return ExitCode::OK;
        }

        // Sans argument : purge les fiches inactives ou supprimées dans toutes les langues
        $count = 0;
        foreach (['fr', 'en', 'es'] as $l) {
            Yii::$app->language = $l;
            $ids = Itineraire::find()
                ->where(['or', ['is_active' => 0], ['not', ['deleted_at' => null]]])
                ->select('syndic_object_id')
                ->column();
            foreach ($ids as $sid) {
                if ($sid) {
                    $cache->purge($l, $sid);
                    $count++;
                }
            }
        }
        $this->stdout("Cache purgé pour $count entrées inactives/supprimées.\n");
        return ExitCode::OK;
    }
}
