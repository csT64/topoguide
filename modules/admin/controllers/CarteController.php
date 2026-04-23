<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;

class CarteController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function actionIndex(): string
    {
        Yii::$app->language = 'fr';
        $itineraires = Itineraire::find()->orderBy('id')->all();
        $cachePath   = Yii::$app->params['pathCacheGmap'];

        $statuts = array_map(function (Itineraire $iti) use ($cachePath) {
            $path = "$cachePath/{$iti->id}.jpg";
            return [
                'id'      => $iti->id,
                'titre'   => $iti->getTitle(),
                'present' => file_exists($path),
                'date'    => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'taille'  => file_exists($path) ? round(filesize($path) / 1024) . ' Ko' : '—',
            ];
        }, $itineraires);

        return $this->render('index', ['statuts' => $statuts]);
    }

    public function actionGenererManquantes(): Response
    {
        $cmd = 'php ' . Yii::getAlias('@app') . '/../yii screenshot/run > /dev/null 2>&1 &';
        exec($cmd);
        Yii::$app->session->addFlash('success', 'Batch de génération lancé en arrière-plan.');
        return $this->redirect(['index']);
    }

    public function actionSupprimer(string $id): Response
    {
        $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
        if (file_exists($path)) {
            unlink($path);
        }
        return $this->redirect(['index']);
    }

    public function actionApercu(string $id): void
    {
        $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
        if (!file_exists($path)) {
            throw new NotFoundHttpException("Capture $id introuvable.");
        }
        Yii::$app->response->sendFile($path, "$id.jpg", ['inline' => true]);
    }
}
