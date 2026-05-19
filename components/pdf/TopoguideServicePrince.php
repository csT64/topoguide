<?php

namespace app\components\pdf;

use Yii;
use yii\base\View;
use app\models\Itineraire;

class TopoguideServicePrince
{
    public function __construct(
        private Itineraire $iti,
        private string $lang
    ) {}

    public function generate(): void
    {
        $pdf = $this->convertToPdf($this->renderHtml());
        $this->sendPdf($pdf);
    }

    public function renderHtml(bool $forPdf = true): string
    {
        $view = new View();
        return $view->renderFile(
            Yii::getAlias('@app/views/topoguide/fiche.php'),
            ['model' => $this->iti, 'lang' => $this->lang, 'forPdf' => $forPdf, 'pdfEngine' => 'prince']
        );
    }

    private function convertToPdf(string $html): string
    {
        $bin    = Yii::$app->params['prince'] ?? '/usr/bin/prince';
        $tmpIn  = tempnam(sys_get_temp_dir(), 'topo_prince_') . '.html';
        $tmpOut = tempnam(sys_get_temp_dir(), 'topo_prince_') . '.pdf';

        file_put_contents($tmpIn, $html);

        $cmd = sprintf(
            '%s %s --pdf-profile=PDF/UA-1 -o %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($tmpIn),
            escapeshellarg($tmpOut)
        );

        exec($cmd, $output, $code);

        @unlink($tmpIn);

        if ($code !== 0 || !file_exists($tmpOut)) {
            throw new \RuntimeException(
                'PrinceXML a échoué (exit ' . $code . ') : ' . implode("\n", $output)
            );
        }

        $pdf = file_get_contents($tmpOut);
        @unlink($tmpOut);

        return $pdf;
    }

    private function sendPdf(string $pdf): void
    {
        $slug     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->iti->getTitle());
        $filename = 'topoguide_' . $slug . '_prince.pdf';

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/pdf');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->headers->set('Content-Length', (string)strlen($pdf));
        Yii::$app->response->content = $pdf;
        Yii::$app->response->send();
        Yii::$app->end();
    }
}
