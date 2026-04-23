<?php

namespace app\controllers;

use yii\web\Controller;

class GmapController extends Controller
{
    public $layout = false;

    public function actionSimple(float $lat, float $lon, int $zoom = 12): string
    {
        return $this->render('simple', compact('lat', 'lon', 'zoom'));
    }

    public function actionGpx(string $file, array $marker = []): string
    {
        return $this->render('gpx', compact('file', 'marker'));
    }

    public function actionKml(string $file, array $marker = []): string
    {
        return $this->render('kml', compact('file', 'marker'));
    }
}
