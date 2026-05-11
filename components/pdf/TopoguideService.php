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
        $bin     = Yii::$app->params['wkhtmltopdf'] ?? '/usr/bin/wkhtmltopdf';
        $webroot = Yii::getAlias('@webroot');
        $mapDir  = Yii::getAlias(Yii::$app->params['pathCacheGmap']);
        $pix     = $webroot . '/pix/pdf';

        $tmpIn     = tempnam(sys_get_temp_dir(), 'topo_') . '.html';
        $tmpOut    = tempnam(sys_get_temp_dir(), 'topo_') . '.pdf';
        $tmpHeader = tempnam(sys_get_temp_dir(), 'topo_hdr_') . '.html';
        $tmpFooter = tempnam(sys_get_temp_dir(), 'topo_ftr_') . '.html';

        file_put_contents($tmpIn,     $html);
        file_put_contents($tmpHeader, $this->buildDecorHtml($pix . '/haut_page.png',    '25mm'));
        file_put_contents($tmpFooter, $this->buildDecorHtml($pix . '/pied_page_noir.png', '20mm'));

        $cmd = sprintf(
            '%s --quiet --encoding utf-8 --print-media-type --allow %s --allow %s'
            . ' --margin-top 27mm --margin-bottom 22mm --margin-left 9mm --margin-right 9mm'
            . ' --header-html %s --header-spacing 0'
            . ' --footer-html %s --footer-spacing 0'
            . ' %s %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($webroot),
            escapeshellarg($mapDir),
            escapeshellarg($tmpHeader),
            escapeshellarg($tmpFooter),
            escapeshellarg($tmpIn),
            escapeshellarg($tmpOut)
        );

        exec($cmd, $output, $code);

        @unlink($tmpIn);
        @unlink($tmpHeader);
        @unlink($tmpFooter);

        if ($code !== 0 || !file_exists($tmpOut)) {
            throw new \RuntimeException(
                'wkhtmltopdf a échoué (exit ' . $code . ') : ' . implode("\n", $output)
            );
        }

        $pdf = file_get_contents($tmpOut);
        @unlink($tmpOut);

        return $pdf;
    }

    // Génère un HTML header ou footer autonome avec l'image en base64.
    // <!DOCTYPE HTML> doit être en première position, sans espace ni BOM.
    private function buildDecorHtml(string $imgPath, string $height): string
    {
        if (!file_exists($imgPath)) {
            return '<!DOCTYPE HTML><html><head></head><body></body></html>';
        }

        $ext  = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
        $b64  = base64_encode(file_get_contents($imgPath));
        $uri  = 'data:' . $mime . ';base64,' . $b64;

        // Concaténation : pas de heredoc pour éviter tout espace parasite avant DOCTYPE
        return '<!DOCTYPE HTML>'
            . '<html><head><meta charset="UTF-8"><style>'
            . 'html,body{margin:0;padding:0;width:100%;height:' . $height . ';}'
            . 'div{width:100%;height:100%;'
            . 'background-image:url(\'' . $uri . '\');'
            . 'background-repeat:repeat-x;'
            . 'background-size:auto 100%;}'
            . '</style></head>'
            . '<body><div></div></body></html>';
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
