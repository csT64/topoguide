<?php

namespace app\components\pdf;

use Yii;
use yii\base\View;
use app\models\Itineraire;

class TopoguideService
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

    // ── HTML rendering ─────────────────────────────────────────────────────────

    public function renderHtml(bool $forPdf = true): string
    {
        $view = new View();
        return $view->renderFile(
            Yii::getAlias('@app/views/topoguide/fiche.php'),
            ['model' => $this->iti, 'lang' => $this->lang, 'forPdf' => $forPdf]
        );
    }

    // ── PDF conversion via wkhtmltopdf ─────────────────────────────────────────

    private function convertToPdf(string $html): string
    {
        $bin    = Yii::$app->params['wkhtmltopdf'] ?? '/usr/bin/wkhtmltopdf';
        $tmpIn  = tempnam(sys_get_temp_dir(), 'topo_') . '.html';
        $tmpOut = tempnam(sys_get_temp_dir(), 'topo_') . '.pdf';

        file_put_contents($tmpIn, $html);

        $webroot = Yii::getAlias('@webroot');
        $mapDir  = Yii::getAlias(Yii::$app->params['pathCacheGmap']);

        $cmd = sprintf(
            '%s --quiet --encoding utf-8 --print-media-type --allow %s --allow %s %s %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($webroot),
            escapeshellarg($mapDir),
            escapeshellarg($tmpIn),
            escapeshellarg($tmpOut)
        );

        exec($cmd, $output, $code);

        @unlink($tmpIn);

        if ($code !== 0 || !file_exists($tmpOut)) {
            throw new \RuntimeException(
                'wkhtmltopdf a échoué (exit ' . $code . ') : ' . implode("\n", $output)
            );
        }

        $pdf = file_get_contents($tmpOut);
        @unlink($tmpOut);

        return $pdf;
    }

    // ── Envoi HTTP ─────────────────────────────────────────────────────────────

    private function sendPdf(string $pdf): void
    {
        $slug     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->iti->getTitle());
        $filename = 'topoguide_' . $slug . '.pdf';

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/pdf');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->headers->set('Content-Length', (string)strlen($pdf));
        Yii::$app->response->content = $pdf;
        Yii::$app->response->send();
        Yii::$app->end();
    }
}
