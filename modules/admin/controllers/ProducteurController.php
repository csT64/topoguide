<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\Producteur;
use app\models\ProducteurSearch;

class ProducteurController extends Controller
{
    public $layout = '@app/modules/admin/views/layouts/admin';

    public function actionIndex(): string
    {
        $searchModel  = new ProducteurSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    public function actionView(string $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Producteur();

        if ($model->load(Yii::$app->request->post())) {
            $model->logo = $this->handleLogoUpload($model) ?? $model->logo;
            if ($model->save()) {
                Yii::$app->session->addFlash('success', 'Producteur créé.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', compact('model'));
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $uploaded = $this->handleLogoUpload($model);
            if ($uploaded !== null) {
                $model->logo = $uploaded;
            }
            if ($model->save()) {
                Yii::$app->session->addFlash('success', 'Producteur mis à jour.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', compact('model'));
    }

    public function actionDelete(string $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->addFlash('success', 'Producteur supprimé.');
        return $this->redirect(['index']);
    }

    private function handleLogoUpload(Producteur $model): ?string
    {
        $file = UploadedFile::getInstance($model, 'logoFile');
        if ($file === null) {
            return null;
        }

        $dir = Yii::getAlias('@webroot/producteur/');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = $model->id . '.png';
        $file->saveAs($dir . $filename);
        return $filename;
    }

    private function findModel(string $id): Producteur
    {
        $model = Producteur::findOne(['id' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException("Producteur $id introuvable.");
        }
        return $model;
    }
}
