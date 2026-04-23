<?php

namespace app\components\pdf;

use Yii;

class TopoguideHelpers
{
    public static function clean(string $text): string
    {
        $text = trim($text);
        $text = str_replace("\n", ' ', $text);
        $text = str_replace("\u{2019}", "'", $text);
        return $text;
    }

    public static function cleanGps(string $val): string
    {
        return str_replace(['°', ' '], ['', ''], $val);
    }

    /**
     * Titre de section niveau 2 : commune de départ + picto difficulté + bandeau type d'itinéraire.
     */
    public static function titre2(MYPDF $pdf, \app\models\Itineraire $iti, string $font, string $stitre, string $diffVal = ''): void
    {
        $webroot = Yii::getAlias('@webroot');

        // Picto niveau de difficulté (coin supérieur droit)
        $niveaux = [
            'Très facile' => 1, 'Very easy' => 1, 'Muy fácil' => 1,
            'Facile'      => 2, 'Easy'      => 2, 'Fácil'     => 2,
            'Moyenne'     => 3, 'Average'   => 3, 'Medio'     => 3,
        ];
        $niv = $niveaux[$diffVal] ?? 4;
        $pictoNiv = "$webroot/pix/pdf/picto-niv-$niv.png";
        if (file_exists($pictoNiv)) {
            $pdf->Image($pictoNiv, 170, 3, 20, 20, 'PNG', '', '', false, 50, '', false, false, 0);
        }

        // Icône localisation
        $imgLoc = "$webroot/pix/pdf/pointeur_localisation.png";
        $html  = '<table border="0" style="min-height:0.5cm;width:21cm;"><tr>';
        $html .= '<td style="vertical-align:middle;" width="0.5cm">';
        if (file_exists($imgLoc)) {
            $html .= '<img src="' . $imgLoc . '">';
        }
        $html .= '</td>';
        $html .= '<td style="vertical-align:top;" width="14cm">';
        $html .= '<table><tr><td>&nbsp;' . self::clean($stitre) . '</td></tr>';
        $html .= '<tr><td><span style="height:15px;background-color:#1f5468;color:#ffffff;font-size:12px;padding:1cm;display:inline-block;"></span></td></tr>';
        $html .= '</table></td></tr></table>';

        $photoY = $pdf->getY();
        $pdf->setY($photoY);
        $pdf->setCellPadding(0);
        $pdf->SetFont($font, 'I', 20);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->writeHTMLCell(200, null, 10, null, $html, 0, 1, false, true, 'J', true);
        $nY = $pdf->getY();

        // Bandeau type d'itinéraire
        $hType    = $pdf->getY() - 10;
        $typeIti  = "$webroot/pix/pdf/typeIti.png";
        $typeVal  = $iti->getTypeVal();
        $accents  = ['é', 'è', 'à', 'ê'];
        $sans     = ['e', 'e', 'a', 'e'];
        $typeDisp = strtoupper(str_replace($accents, $sans, $typeVal));

        $pdf->setCellPaddings('10', '0', '10', '0');
        $htmlType = '<span style="width:20px;display:block;"></span>'
                  . '<span color="#ffffff" style="font-size:16px;" bgcolor="#1f5468">&nbsp;' . $typeDisp . '&nbsp;</span>';

        if (file_exists($typeIti)) {
            $pdf->Image($typeIti, 10.4, $hType + 1.2, 0, '7.25', 'PNG', '', 'M', false, '16', '72', false, false, 0, false, false, false);
        }
        $pdf->writeHTMLCell('', 10, 3.9, $hType, $htmlType, '0', 1, 0, true, 'L', true);
        $pdf->setY($nY);
    }

    /**
     * Titre de section niveau 3 avec icône optionnelle.
     */
    public static function titre3(MYPDF $pdf, string $font, string $text, ?string $imgPath = null): void
    {
        $sY = $pdf->getY() + 5;
        $pdf->setY($sY);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetFont($font, 'B', 20);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 12, str_repeat(' ', 7) . $text, 0, 1, 'L', true, '', 1, false, 'T', 'C');
        $sY = $pdf->getY();

        if ($imgPath && file_exists($imgPath)) {
            $savedX = $pdf->getX();
            $savedY = $pdf->getY();
            $pdf->Image($imgPath, $savedX + 2, $sY - 10, 8, 8, null, '', 'T', false, 300, 'L', false, false, 0, false);
            $pdf->setXY($savedX, $savedY);
        }
    }
}
