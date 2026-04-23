<?php

namespace app\components\pdf;

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
        $headerImg = dirname(__DIR__, 3) . '/web/pix/pdf/haut_page.png';
        if (file_exists($headerImg)) {
            $this->Image($headerImg, 0, 0, 210, 0, 'PNG');
        }
    }

    public function Footer(): void
    {
        $footerImg = dirname(__DIR__, 3) . '/web/pix/pdf/pied_page_noir.png';
        if (file_exists($footerImg)) {
            $this->Image($footerImg, 0, 280, 210, 0, 'PNG');
        }
        $this->SetY(282);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 0, $this->footerText, 0, 0, 'C');
    }
}
