<?php

namespace app\components\pdf;

use MYPDF;

class TopoguideHelpers
{
    public static function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\u{2019}", "'", $text); // apostrophe typographique
        return $text;
    }

    public static function cleanGps(string $val): string
    {
        return trim(str_replace(['°', ' '], '', $val));
    }

    public static function titre2(
        MYPDF $pdf,
        object $data,
        string $font,
        string $stitre,
        string $diffVal = ''
    ): void {
        $pdf->SetFont($font, 'B', 11);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(0, 6, $stitre, 0, 1, 'L', true);

        if ($diffVal) {
            $picto = dirname(__DIR__, 3) . "/web/pix/pdf/picto-difficulte-$diffVal.png";
            if (file_exists($picto)) {
                $pdf->Image($picto, $pdf->GetX(), $pdf->GetY() - 6, 6, 6, 'PNG');
            }
        }
    }

    public static function titre3(MYPDF $pdf, string $font, string $text, string $img = ''): void
    {
        $pdf->SetFont($font, 'B', 10);
        if ($img && file_exists($img)) {
            $pdf->Image($img, $pdf->GetX(), $pdf->GetY(), 5, 5, 'PNG');
            $pdf->SetX($pdf->GetX() + 6);
        }
        $pdf->Cell(0, 5, $text, 0, 1, 'L');
    }
}
