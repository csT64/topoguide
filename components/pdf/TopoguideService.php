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

    // ── Debug : accès direct aux HTML intermédiaires ───────────────────────────

    public function debugHeaderHtml(): string
    {
        return $this->buildHeaderHtml(Yii::getAlias('@webroot') . '/pix/pdf');
    }

    public function debugFooterHtml(): string
    {
        return $this->buildFooterHtml(
            Yii::getAlias('@webroot') . '/pix/pdf/pied_page_noir.png',
            '20mm'
        );
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
        file_put_contents($tmpHeader, $this->buildHeaderHtml($pix));
        file_put_contents($tmpFooter, $this->buildFooterHtml($pix . '/pied_page_noir.png', '20mm'));

        $cmd = sprintf(
            '%s --quiet --encoding utf-8 --print-media-type'
            . ' --enable-local-file-access'
            . ' --allow %s --allow %s'
            . ' --margin-top 25mm --margin-bottom 20mm --margin-left 0 --margin-right 0'
            . ' --header-html %s --header-spacing 0'
            . ' --footer-html %s --footer-spacing 0'
            . ' --title %s'
            . ' %s %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($webroot),
            escapeshellarg($mapDir),
            escapeshellarg($tmpHeader),
            escapeshellarg($tmpFooter),
            escapeshellarg($this->iti->getTitle()),
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

    private function buildFooterHtml(string $imgPath, string $height): string
    {
        $producteur = \app\models\Producteur::findOne($this->iti->getProducteurId());

        $parts = [];
        if ($producteur) {
            if ($producteur->raison_sociale) $parts[] = '<strong>' . htmlspecialchars($producteur->raison_sociale) . '</strong>';
            foreach (['adresse_1', 'adresse_2', 'adresse_3'] as $f) {
                if (!empty($producteur->$f)) $parts[] = htmlspecialchars($producteur->$f);
            }
            $cp = trim(($producteur->code_postal ?? '') . ' ' . ($producteur->commune ?? ''));
            if ($cp) $parts[] = htmlspecialchars($cp);
            if ($producteur->telephone) $parts[] = htmlspecialchars($producteur->telephone);
            if ($producteur->url)       $parts[] = htmlspecialchars($producteur->url);
        }
        $adresse = implode(' &mdash; ', $parts);

        $bgCss = file_exists($imgPath)
            ? 'background-image:url("file://' . $imgPath . '");background-repeat:repeat-x;background-size:100%;'
            : '';

        return '<html style="margin:0;padding:0;"><head><meta charset="UTF-8"><style>'
            . '*{margin:0;padding:0;}'
            . 'html,body{width:100%;}'
            . 'body{' . $bgCss . '}'
            . 'table{width:100%;height:2.5cm;border-collapse:collapse;}'
            . 'td{color:#fff;font-family:Arial,sans-serif;font-size:11pt;text-align:center;'
            . 'vertical-align:middle;padding:0 9mm;}'
            . '</style></head>'
            . '<body style="margin:0;padding:0;">'
            . '<table><tr><td>' . $adresse . '</td></tr></table>'
            . '</body></html>';
    }

    private function buildHeaderHtml(string $pix): string
    {
        $imgPath  = $pix . '/haut_page.png';
        $difficulte = $this->iti->getDifficulteVal();
        $diffMap  = [
            'Très facile' => 1, 'Very easy' => 1, 'Muy fácil' => 1,
            'Facile'      => 2, 'Easy'      => 2, 'Fácil'     => 2,
            'Moyenne'     => 3, 'Average'   => 3, 'Medio'     => 3,
        ];
        $diffNiv  = $diffMap[$difficulte] ?? 4;
        $diffPath = $pix . '/picto-niv-' . $diffNiv . '.png';

        $bgImg   = file_exists($imgPath)  ? '<img class="bg"   src="file://' . $imgPath  . '" alt="">' : '';
        $diffImg = file_exists($diffPath) ? '<img class="diff" src="file://' . $diffPath . '" alt="">' : '';

        // Valeurs empiriques validées sur QtWebKit 5.12 :
        // .bg  width:1480px — couvre toute la largeur A4 à la résolution de rendu wkhtmltopdf
        // .diff left:160%  — positionne le picto en haut à droite dans ce contexte de rendu
        return '<html><head><meta charset="UTF-8"><style>'
            . 'html,body{margin:0;padding:0;width:210mm;height:80px;}'
            . 'body{position:relative;height:180px;}'
            . '.bg{display:block;position:relative;width:1480px;margin:0;padding:0;}'
            . '.diff{position:absolute;top:40%;left:160%;}'
            . '</style></head>'
            . '<body>' . $bgImg . $diffImg . '</body></html>';
    }

    // ── Envoi HTTP ─────────────────────────────────────────────────────────────

    private function sendPdf(string $pdf): void
    {
        $slug     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->iti->getTitle());
        $filename = 'topoguide_' . $slug . '_wk.pdf';

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/pdf');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        Yii::$app->response->headers->set('Content-Length', (string)strlen($pdf));
        Yii::$app->response->content = $pdf;
        Yii::$app->response->send();
        Yii::$app->end();
    }
}
