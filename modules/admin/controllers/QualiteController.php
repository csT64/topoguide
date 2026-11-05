<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use app\models\QualiteService;

class QualiteController extends Controller
{

    public function actionIndex(): string
    {
        $service = new QualiteService();
        $results = $service->run();

        return $this->render('index', [
            'overview' => $results['overview'],
            'sections' => $results['sections'],
            'generatedAt' => date('d/m/Y à H:i:s'),
        ]);
    }
}
