<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Ville;
use app\models\VilleSearch;

class VilleController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function actionIndex(): string
    {
        $searchModel  = new VilleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    public function actionCreate(): Response|string
    {
        $model = new Ville();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', 'Ville créée.');
            return $this->redirect(['index']);
        }

        return $this->render('create', compact('model'));
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success', 'Ville mise à jour.');
            return $this->redirect(['index']);
        }

        return $this->render('update', compact('model'));
    }

    public function actionDelete(string $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->addFlash('success', 'Ville supprimée.');
        return $this->redirect(['index']);
    }

    private function findModel(string $id): Ville
    {
        $model = Ville::findOne(['ville_id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException("Ville $id introuvable.");
        }
        return $model;
    }
}
