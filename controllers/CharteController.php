<?php

namespace app\controllers;

use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Page spécimen de la charte graphique Tourisme 64 : tous les composants, dans le thème
 * courant. Sert de référence visuelle et de page de contrôle pour outils/audit-contrastes.js.
 * Réservée aux utilisateurs connectés (index.php?r=charte/index).
 */
class CharteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'pagination' => new Pagination(['totalCount' => 240, 'pageSize' => 20, 'page' => 2]),
            'icones' => array_map(fn ($f) => basename($f, '.svg'), glob(\Yii::getAlias('@webroot/icons/*.svg')) ?: []),
        ]);
    }
}
