<?php

namespace app\components\pdf;

use Yii;
use app\models\Itineraire;
use app\models\Producteur;
use TCPDF_FONTS;

class TopoguideService
{
    private Itineraire $iti;
    private string     $lang;
    private string     $webroot;

    public function __construct(Itineraire $iti, string $lang)
    {
        $this->iti     = $iti;
        $this->lang    = $lang;
        $this->webroot = Yii::getAlias('@webroot');
    }

    public function generate(): void
    {
        $iti  = $this->iti;
        $lang = $this->lang;
        $web  = $this->webroot;
        $pix  = $web . '/pix/pdf';
        $cdn  = Yii::$app->params['mediaCdnUrl'];

        // --- Polices ---
        $fontDir   = Yii::getAlias('@app') . '/fonts/';
        $fontBody  = file_exists($fontDir . 'futuramediumbt.ttf')
            ? TCPDF_FONTS::addTTFfont($fontDir . 'futuramediumbt.ttf',  'TrueTypeUnicode', '', 96)
            : 'helvetica';
        $fontTitle = file_exists($fontDir . 'FuturaHeavyfont.ttf')
            ? TCPDF_FONTS::addTTFfont($fontDir . 'FuturaHeavyfont.ttf', 'TrueTypeUnicode', '', 96)
            : 'helvetica';

        // --- Producteur ---
        $producteurId   = $iti->getProducteurId();
        $producteur     = Producteur::findOne($producteurId) ?? [];
        $producteurArr  = $producteur ? $producteur->attributes : [];

        // Logo producteur (fichier local) avec fallback sur logo département
        $dept       = $iti->getDept();
        $logoLocal  = $web . '/producteur/' . $producteurId . '.png';
        $logoFallbk = $pix . '/departement/logo' . $dept . '.png';
        $logoSrc    = file_exists($logoLocal)  ? $logoLocal  :
                     (file_exists($logoFallbk) ? $logoFallbk : null);

        // Journaliser les producteurs introuvables
        if (empty($producteurArr)) {
            $logFile = Yii::getAlias(Yii::$app->params['logFile']);
            $msg = date('Y-m-d H:i:s') . " [producteur] id=$producteurId auteur={$iti->auteur} (no row)\n";
            @file_put_contents($logFile, $msg, FILE_APPEND);
        }

        // --- Champs JSON décodés ---
        $j_difficulte = json_decode($iti->difficulte ?? '', true) ?: [];
        $j_type       = json_decode($iti->type       ?? '', true) ?: [];
        $j_typologie  = json_decode($iti->typologie  ?? '', true) ?: [];
        $j_duree      = json_decode($iti->duree      ?? '', true) ?: [];
        $j_photo      = json_decode($iti->photo      ?? '', true) ?: [];
        $j_etapes     = json_decode($iti->etapes     ?? '', true) ?: [];
        $j_poi        = json_decode($iti->point_d_interet   ?? '', true) ?: [];
        $j_equipement = json_decode($iti->equipement        ?? '', true) ?: [];
        $j_attention  = json_decode($iti->point_d_attention ?? '', true) ?: [];

        $difficulteVal = $iti->getDifficulteVal();
        $typeVal       = $iti->getTypeVal();
        $dureeVal      = $iti->getDureeVal();
        $boucleVal     = $iti->getBoucleVal();

        // --- Footer producteur ---
        $footer = $this->buildFooter($producteurArr);

        // --- PDF init ---
        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false, true);
        $pdf->SetCreator('Topoguide Tourisme 64');
        $pdf->SetAuthor('ADT Béarn Pays basque');
        $pdf->SetTitle(TopoguideHelpers::clean($iti->getTitle()));
        $pdf->SetMargins(9, 23, 9);
        $pdf->SetFooterMargin(10);
        $pdf->setPrintFooter(true);
        $pdf->setFooterData([255, 255, 255], [255, 0, 0]);
        $pdf->setFooterText($footer);

        //==========
        // PAGE 1
        //==========
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->AddPage('P', 'A4');
        $pdf->SetAutoPageBreak(true, 25);

        $aX = $pdf->getX();
        $aY = $pdf->getY();

        // Titre principal
        $docTitle = TopoguideHelpers::clean($iti->getTitle());
        $pdf->setCellPaddings(0, 0, 20, 0);
        $pdf->SetFont($fontTitle, 'B', 30);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->setXY($aX, $aY);
        $pdf->Cell(140, 0, $docTitle, 0, 1, 'L', true, '', 1, false, 'T', 'C');
        $bX = $pdf->getX();
        $bY = $pdf->getY();
        $pdf->setXY($aX, $aY);

        // Logo producteur
        if ($logoSrc) {
            $pdf->Image($logoSrc, 150, 25, 50, 23, null, '', 'N', false, 300, '', false, false, 0, 'CM');
        }
        $pdf->setXY($bX, $bY);

        TopoguideHelpers::titre2($pdf, $iti, $fontBody, (string)$iti->commune_depart, $difficulteVal);

        // Descriptif + photos
        $txt = (string)($iti->descriptif ?? '');
        $pdf->setCellPadding(0);
        $pdf->SetFont($fontBody, '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $sY = $pdf->getY();

        $srcDefault = $pix . '/default-1000-625.png';
        $src01 = $srcDefault;
        if (!empty($j_photo[0])) {
            $src01 = is_array($j_photo[0]) ? ($j_photo[0]['Photo']['Url'] ?? $srcDefault) : $j_photo[0];
        }

        $src02 = null;
        if (!empty($j_photo[1])) {
            $src02 = is_array($j_photo[1]) ? ($j_photo[1]['Photo']['Url'] ?? '') : $j_photo[1];
        }

        $pdf->setXY($pdf->getX(), $sY + 2.5);

        if ($src02) {
            $bloc  = '<table>';
            $bloc .= '<tr><td colspan="2" style="width:19cm;">' . $txt . '<br></td></tr>';
            $bloc .= '<tr><td><img style="width:9.3cm;" src="' . $src01 . '"></td>';
            $bloc .= '<td align="right"><img style="width:9.3cm;" src="' . $src02 . '"></td></tr>';
            $bloc .= '</table>';
        } else {
            $bloc  = '<table>';
            $bloc .= '<tr><td style="width:9cm;">' . $txt . '<br></td>';
            $bloc .= '<td style="width:1cm;">&nbsp;</td>';
            $bloc .= '<td style="width:9cm;"><img src="' . $src01 . '"></td></tr>';
            $bloc .= '</table>';
        }
        $pdf->writeHTMLCell(190, null, 10, null, $bloc, 0, 2, false, true, 'J', true);

        // Labels multilingues
        [$departLabel, $arriveeLabel, $distanceLabel, $deniveleLabel, $dureeLabel,
         $homologueLabel, $itineraireLabel, $boucleLabel, $appelUrgenceLabel,
         $balisageLabel, $alerteMessage] = $this->getLabels($lang);

        // Ligne séparatrice
        $style = ['width' => 0.5, 'color' => [31, 84, 104]];
        $sY    = $pdf->getY();
        $pdf->Line(10, $sY + 5, 200, $sY + 5, $style);

        // Tableau Départ / Arrivée / Distance / Dénivelé / Durée
        $html1 = '';
        if (!empty($iti->homologue_ffr)) {
            $html1 .= '<b style="color:#E21D3B">' . $homologueLabel . '</b><br><br>';
        }

        $imgWhere    = $pix . '/picto-where.svg';
        $imgDistance = $pix . '/picto-distance.svg';
        $imgDeniv    = $pix . '/picto-denivele.svg';
        $imgDuree    = $pix . '/picto-duree.svg';

        $html1 .= '<table style="height:1cm;"><tbody><tr>';
        $html1 .= '<td rowspan="2" style="vertical-align:middle;width:40px;text-align:center"><p style="line-height:1cm;">'
                . (file_exists($imgWhere) ? '<img src="' . $imgWhere . '" height="32" alt="">' : '') . '</p></td>';
        $html1 .= '<td style="width:6.4cm;padding:5px;border-right:1px solid #1f5468;">' . $departLabel . ' ' . htmlspecialchars((string)$iti->commune_depart) . '</td>';
        $html1 .= '<td rowspan="2" style="vertical-align:middle;width:40px;text-align:center"><p style="line-height:1cm;">'
                . (file_exists($imgDistance) ? '<img src="' . $imgDistance . '" width="32" height="32" alt="">' : '') . '</p></td>';
        $html1 .= '<td style="border-right:1px solid #1f5468;">' . $distanceLabel . ' </td>';

        $hasDenivele = ($iti->denivele !== null && $iti->denivele != '')
                    || ($iti->denivele_negatif_cumule !== null && $iti->denivele_negatif_cumule != '');
        if ($hasDenivele) {
            $html1 .= '<td rowspan="2" style="vertical-align:middle;width:40px;text-align:center;">'
                    . '<p style="line-height:1cm;">' . (file_exists($imgDeniv) ? '<img src="' . $imgDeniv . '" width="32" height="32" alt="">' : '') . '</p></td>';
            $html1 .= '<td style="border-right:1px solid #1f5468;">' . $deniveleLabel . '</td>';
        }
        if ($dureeVal !== '') {
            $html1 .= '<td rowspan="2" style="vertical-align:middle;width:40px;text-align:center"><p style="line-height:1cm;">'
                    . (file_exists($imgDuree) ? '<img src="' . $imgDuree . '" width="32" height="32" alt="">' : '') . '</p></td>';
            $html1 .= '<td>' . $dureeLabel . ' </td>';
        }
        $html1 .= '</tr><tr>';
        $html1 .= '<td style="border-right:1px solid #1f5468;">' . $arriveeLabel . ' ' . htmlspecialchars((string)$iti->commune_arrivee) . '</td>';
        $html1 .= '<td style="border-right:1px solid #1f5468;">' . $iti->distance_km . ' km</td>';
        if ($hasDenivele) {
            $html1 .= '<td style="border-right:1px solid #1f5468;">' . $iti->denivele . ' m</td>';
        }
        if ($dureeVal !== '') {
            $html1 .= '<td>' . $dureeVal . '</td>';
        }
        $html1 .= '</tr></tbody></table>';

        $sY = $pdf->getY();
        $pdf->writeHTMLCell(190, null, 10, $sY + 12, $html1, 0, 2, false, true, 'L', true);

        // Boucle / Parking / Urgence / Balisage
        $balisage   = (string)($iti->balisage_fichier ?? '');
        $wBalisage  = !empty($balisage) ? 1 : 0;
        $wBoucle    = ($boucleVal === 'Boucle') ? 3 : 0;

        $imgParking = $pix . '/picto-parking.svg';
        $imgLoop    = $pix . '/picto-loop.png';
        $img112     = $pix . '/picto-112.svg';
        $imgAlerte  = $pix . '/picto-attention.svg';

        $htmlParking = '';
        if (!empty($iti->parking)) {
            $htmlParking = '<td style="vertical-align:middle;width:40px;text-align:center">'
                         . '<p style="line-height:1cm;">' . (file_exists($imgParking) ? '<img src="' . $imgParking . '" width="20" height="20" alt="">' : '') . '</p></td>'
                         . '<td style="width:' . ($wBoucle + $wBalisage) . 'cm;padding:5px;border-right:1px solid #1f5468;">'
                         . htmlspecialchars((string)$iti->parking) . '</td>';
        }

        $html3 = '';
        if ($boucleVal === 'Boucle') {
            $html3 .= '<td style="width:1cm;vertical-align:middle;text-align:center"><p style="line-height:1cm;">'
                    . (file_exists($imgLoop) ? '<img src="' . $imgLoop . '" width="32" alt="">' : '') . '</p></td>'
                    . '<td style="width:' . ($wBoucle + $wBalisage) . 'cm;padding:5px;border-right:1px solid #1f5468;">&nbsp;'
                    . $itineraireLabel . ' <b>' . $boucleLabel . '</b></td>';
        }

        $html3 .= $htmlParking
                . '<td style="width:1cm;vertical-align:middle;text-align:center;">'
                . '<p style="line-height:1cm;">' . (file_exists($img112) ? '<img src="' . $img112 . '" width="32" height="32" alt="">' : '') . '</p></td>'
                . '<td style="width:' . ($wBoucle + $wBalisage) . 'cm;padding:5px;border-right:1px solid #1f5468;">'
                . $appelUrgenceLabel . '&nbsp;<b>112</b>&nbsp;</td>';

        // Balisage
        if (!empty($balisage)) {
            $ext = strtolower(pathinfo($balisage, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'gif', 'jpg', 'jpeg'])) {
                $balisageSrc = (str_starts_with($balisage, 'http')) ? $balisage : $cdn . '/' . $balisage;
                $html3 .= '<td style="width:2cm;padding:5px;border-left:1px solid #1f5468;">&nbsp;&nbsp;' . $balisageLabel . '&nbsp;</td>'
                        . '<td style="vertical-align:middle;text-align:center;width:1cm;">'
                        . '<p style="line-height:1cm;"><img src="' . $balisageSrc . '" width="32" height="32" alt=""></p></td>';
            }
        }

        $htmlAlerte2 = '<table style="margin-top:1cm;" border="0"><tr>'
                     . '<td width="1cm"><img style="float:left" src="' . $imgAlerte . '" width="32" height="32" alt=""></td>'
                     . '<td width="18cm"><p style="line-height:0.5cm;"><font size="9">' . $alerteMessage . '</font></p></td>'
                     . '</tr></table>';

        if (!empty($html3)) {
            $html3 = '<table width="19cm" border="0" style="display:block;width:19cm;height:2cm;">'
                   . '<tr style="width:19cm;">' . $html3 . '</tr></table>';
            $sY = $pdf->getY();
            $pdf->Line(10, $sY + 5, 200, $sY + 5, $style);
        }

        $sY = $pdf->getY() + 10;
        $pdf->setCellPadding(0);
        $pdf->SetFont($fontBody, '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->writeHTMLCell(190, null, 10, $sY, $html3, 0, 1, false, true, 'L', true);

        if (!empty($html3)) {
            $sY = $pdf->getY();
            $pdf->Line(10, $sY + 5, 200, $sY + 5, $style);
            $pdf->writeHTMLCell(190, null, 10, $sY + 8, $htmlAlerte2, 0, 1, false, true, 'L', true);
        }

        //==========
        // PAGE 2
        //==========

        // Points d'intérêt
        if (!empty($j_poi)) {
            $texte = match($lang) { 'en' => "Don't miss out", 'es' => "No te lo pierdas", default => "À ne pas manquer" };
            TopoguideHelpers::titre3($pdf, $fontTitle, $texte, $pix . '/picto-poi.png');

            $htmlParts = [];
            $sep = '';
            foreach ($j_poi as $poi) {
                $nom  = is_array($poi) ? trim($poi['Nom'] ?? '')        : trim((string)$poi);
                $desc = is_array($poi) ? trim($poi['Descriptif'] ?? '') : '';
                if ($nom !== '') {
                    $nom = '<strong style="color:#1f5468;">' . rtrim($nom, '.') . '.</strong> ';
                }
                $htmlParts[] = $sep . '&bull; ' . $nom . $desc;
                $sep = '<br>';
            }
            $pdf->setCellPadding(3);
            $pdf->SetFont($fontBody, '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->writeHTMLCell(190, 20, null, null, implode('', $htmlParts), 0, 1, false, true, 'J', true);
        }

        // Carte JPG
        $mapFile = Yii::$app->params['pathCacheGmap'] . '/' . $iti->id . '.jpg';
        if (file_exists($mapFile)) {
            $pdf->Image($mapFile, null, null, 190, 120, null, '', 'N', true, 300, 'R', false, false, false, false);
        }

        // Étapes
        if (!empty($j_etapes)) {
            $titre = match($lang) { 'en' => 'Stages', 'es' => 'Etapas', default => 'Étapes' };
            TopoguideHelpers::titre3($pdf, $fontBody, $titre, $pix . '/picto-poi.png');

            $html = '';
            foreach ($j_etapes as $idx => $etape) {
                if (is_array($etape)) {
                    // V3 lowercase keys take priority over legacy uppercase keys
                    $ordre = $etape['ordre'] ?? $etape['Ordre'] ?? null;
                    $title = (string)($etape['nom_etape']  ?? $etape['NomEtape']   ?? '');
                    $desc  = (string)($etape['descriptif'] ?? $etape['Descriptif'] ?? '');
                    $step  = $ordre !== null ? (int)$ordre : ($idx + 1);
                } else {
                    $step  = $idx + 1;
                    $title = (string)$etape;
                    $desc  = '';
                }
                $html .= '<strong>' . $step . '.</strong> ';
                if ($title !== '') {
                    $html .= '<strong style="color:#1f5468">'
                           . trim($title)
                           . (str_ends_with(trim($title), '.') ? '' : '.')
                           . '</strong> ';
                }
                $html .= trim($desc) . '<br>';
            }
            $pdf->setCellPadding(0);
            $pdf->SetFont($fontBody, '', 9.6);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->writeHTMLCell(null, null, null, null, $html, 0, 1, false, true, 'L', true);
        }

        // Équipements
        if (!empty($j_equipement)) {
            $label = match($lang) { 'en' => 'Equipment', 'es' => 'Equipamiento', default => 'Équipements' };
            TopoguideHelpers::titre3($pdf, $fontBody, $label, $pix . '/picto-poi.png');

            $html = $sep = '';
            foreach ($j_equipement as $equi) {
                $nom = is_array($equi) ? ($equi['Nom'] ?? '') : (string)$equi;
                if ($nom !== '') {
                    $html .= $sep . '&bull; ' . $nom;
                    $sep = '<br>';
                }
            }
            $pdf->setCellPadding(0);
            $pdf->SetFont($fontBody, '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->writeHTMLCell(null, null, null, null, $html, 0, 1, false, true, 'L', true);
        }

        // Points d'attention
        if (!empty($j_attention)) {
            $label = match($lang) { 'en' => 'Warning', 'es' => 'Advertencias', default => 'Attention' };
            TopoguideHelpers::titre3($pdf, $fontBody, $label, $pix . '/picto-poi.png');

            $html = $sep = '';
            foreach ($j_attention as $poi) {
                $desc = is_array($poi) ? ($poi['Descriptif'] ?? '') : (string)$poi;
                if ($desc !== '') {
                    $html .= $sep . '&bull; ' . trim($desc);
                    $sep = '<br>';
                }
            }
            $pdf->setCellPadding(3);
            $pdf->SetFont($fontBody, '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->writeHTMLCell(null, null, null, null, $html, 0, 1, false, true, 'J', true);
        }

        // Lien réussirmarando (hors vélo)
        if (!str_contains($typeVal, 'élo')) {
            $texteLien = match($lang) {
                'en' => 'To properly prepare for your hike and adopt the right behavior in the mountains, visit https://reussirmarando.com',
                'es' => 'Para preparar bien tu excursión y adoptar los buenos hábitos en la montaña, visita https://reussirmarando.com',
                default => 'Pour bien préparer sa rando et adopter les bons gestes en montagne, rendez-vous sur https://reussirmarando.com',
            };
            $pdf->writeHTML(
                '<a href="https://reussirmarando.com?utm_source=pdf_iti&utm_medium=pdf&utm_campaign=pdf-iti">' . $texteLien . '</a>',
                false, false, false, false, 'L'
            );
        }

        // Sortie PDF inline
        $pdf->Output($iti->id . '.pdf', 'I');
    }

    private function buildFooter(array $p): string
    {
        $footer = '';
        if (!empty($p['raison_sociale'])) {
            $footer .= $p['raison_sociale'] . "\n";
        }
        foreach (['adresse_1', 'adresse_2', 'adresse_3'] as $field) {
            if (!empty($p[$field])) {
                $footer .= $p[$field] . ' ';
            }
        }
        $footer .= trim(($p['code_postal'] ?? '') . ' ' . ($p['commune'] ?? ''));

        if (!empty($p['telephone'])) {
            $tel = trim(substr($p['telephone'], 0, strpos($p['telephone'] . '|', '|')));
            $footer .= ' | ' . htmlspecialchars($tel);
        }
        if (!empty($p['url'])) {
            $url = preg_replace(['|^https?://|', '|/$|'], '', trim($p['url']));
            $url = trim(substr($url, 0, strpos($url . '|', '|')));
            $footer .= ' | ' . htmlspecialchars($url);
        }

        return trim($footer);
    }

    private function getLabels(string $lang): array
    {
        return match($lang) {
            'en' => [
                'Departure :', 'Arrival :', 'Distance :', 'Elevation Gain :', 'Duration :',
                'FFRandonnée Certified',
                'Itinerary :', 'Loop', 'Emergency call :', 'Signage',
                'The Basque and Béarnaise mountains are pastoral areas. Avoid going with your dog.<br>In all cases, keep it on a leash. Thank you!',
            ],
            'es' => [
                'Salida :', 'Llegada :', 'Distancia :', 'Desnivel :', 'Duración :',
                'Homologado FFRandonnée',
                'Itinerario :', 'Bucle', 'Llamada de emergencia :', 'Señalización',
                'Las montañas vascas y bearnaesas son espacios pastorales. Evite salir con su perro.<br>En todos los casos, manténgalo con correa. ¡Gracias!',
            ],
            default => [
                'Départ :', 'Arrivée :', 'Distance :', 'Dénivelé :', 'Durée :',
                'Homologué FFRandonnée',
                'Itinéraire :', 'Boucle', "Appel d'urgence :", 'Balisage',
                'Les montagnes basques et béarnaises sont des espaces pastoraux. Evitez de partir avec votre chien.<br>Dans tous les cas, tenez-le en laisse. Merci !',
            ],
        };
    }
}
