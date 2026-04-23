<?php

namespace app\components\pdf;

use Yii;
use TCPDF;

class MYPDF extends TCPDF
{
    private string $footerText = '';

    public function setFooterText(string $text): void
    {
        $this->footerText = $text;
    }

    public function Header(): void
    {
        $img = Yii::getAlias('@webroot') . '/pix/pdf/haut_page.png';
        if (file_exists($img)) {
            $this->Image($img, 0, 0, 210, 0, 'PNG', '', '', false, 300, '', false, false, 0);
        }
    }

    public function Footer(): void
    {
        $img = Yii::getAlias('@webroot') . '/pix/pdf/pied_page_noir.png';
        if (file_exists($img)) {
            $this->Image($img, 0, 277, 210, 0, 'PNG', '', '', false, 300, '', false, false, 0);
        }
        $this->SetY(-15);
        $this->SetFont('helvetica', 'B', 8);
        $this->SetTextColor(255, 255, 255);
        $this->MultiCell(0, 0, $this->footerText, 'T', 'C', false, 0);
    }
}
