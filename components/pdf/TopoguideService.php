<?php

namespace app\components\pdf;

use Yii;
use app\models\Itineraire;

class TopoguideService
{
    private Itineraire $iti;
    private string     $lang;

    public function __construct(Itineraire $iti, string $lang)
    {
        $this->iti  = $iti;
        $this->lang = $lang;
    }

    public function generate(): void
    {
        $pdf = $this->createPdf();
        $this->buildContent($pdf);
        $pdf->Output($this->iti->id . '.pdf', 'I');
    }

    private function createPdf(): MYPDF
    {
        $fontPath = Yii::getAlias(Yii::$app->params['pathFontsTcpdf']);
        define('K_PATH_FONTS', $fontPath . '/');

        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Topoguide');
        $pdf->SetAuthor('Tourisme 64');
        $pdf->SetTitle($this->iti->getTitle());
        $pdf->SetMargins(10, 30, 10);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $producteur = $this->iti->producteur;
        if ($producteur) {
            $footer = implode(' — ', array_filter([
                $producteur->raison_sociale,
                $producteur->telephone,
                $producteur->url,
            ]));
            $pdf->setFooterText($footer);
        }

        return $pdf;
    }

    private function buildContent(MYPDF $pdf): void
    {
        // Construction du contenu PDF — à migrer depuis iti_iti_pdf.php (moteur-v14)
        // Phase 2 du plan de migration
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, TopoguideHelpers::clean($this->iti->getTitle()), 0, 1);
    }
}
