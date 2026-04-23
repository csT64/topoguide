<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Itineraire;
use app\models\ItineraireSearch;
use app\components\map\ScreenshotService;

class ItineraireController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function actionIndex(): string
    {
        $searchModel  = new ItineraireSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    public function actionView(string $id, string $lang = 'fr'): string
    {
        return $this->render('view', ['model' => $this->findModel($id, $lang)]);
    }

    public function actionCreate(): Response|string
    {
        Yii::$app->language = Yii::$app->request->get('lang', 'fr');
        $model = new Itineraire();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', 'Itinéraire créé.');
            return $this->redirect(['view', 'id' => $model->id, 'lang' => Yii::$app->language]);
        }

        return $this->render('create', compact('model'));
    }

    public function actionUpdate(string $id, string $lang = 'fr'): Response|string
    {
        $model = $this->findModel($id, $lang);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', 'Itinéraire mis à jour.');
            return $this->redirect(['view', 'id' => $model->id, 'lang' => $lang]);
        }

        return $this->render('update', compact('model'));
    }

    public function actionDelete(string $id, string $lang = 'fr'): Response
    {
        $this->findModel($id, $lang)->delete();
        Yii::$app->session->addFlash('success', 'Itinéraire supprimé.');
        return $this->redirect(['index']);
    }

    public function actionGenererCarte(string $id): Response
    {
        Yii::$app->language = 'fr';
        $iti = $this->findModel($id, 'fr');
        $ok  = (new ScreenshotService())->captureOne($iti);
        Yii::$app->session->addFlash(
            $ok ? 'success' : 'error',
            $ok ? "Carte $id générée." : "Échec de la capture pour $id."
        );
        return $this->redirect(['index']);
    }

    public function actionSupprimerCarte(string $id): Response
    {
        $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
        if (file_exists($path)) {
            unlink($path);
            Yii::$app->session->addFlash('success', "Carte $id supprimée.");
        }
        return $this->redirect(['index']);
    }

    private function findModel(string $id, string $lang = 'fr'): Itineraire
    {
        Yii::$app->language = $lang;
        $model = Itineraire::findOne(['id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException("Itinéraire $id introuvable.");
        }
        return $model;
    }
}
