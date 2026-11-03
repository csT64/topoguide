<?php

namespace app\models;

use Yii;
use yii\db\Connection;

class QualiteService
{
    private Connection $db;

    public function __construct()
    {
        $this->db = Yii::$app->db;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Point d'entrée
    // ─────────────────────────────────────────────────────────────────────────

    public function run(): array
    {
        $totalFr = (int)$this->db->createCommand('SELECT COUNT(*) FROM fr')->queryScalar();
        $activeFr = (int)$this->db->createCommand('SELECT COUNT(*) FROM fr WHERE is_active = 1')->queryScalar();
        $totalEn = (int)$this->db->createCommand('SELECT COUNT(*) FROM en')->queryScalar();
        $totalEs = (int)$this->db->createCommand('SELECT COUNT(*) FROM es')->queryScalar();
        $totalProd = (int)$this->db->createCommand('SELECT COUNT(*) FROM producteur')->queryScalar();
        $prodRef   = (int)$this->db->createCommand(
            "SELECT COUNT(DISTINCT auteur) FROM fr WHERE auteur IS NOT NULL AND auteur != ''"
        )->queryScalar();

        return [
            'overview' => [
                'totalFr'    => $totalFr,
                'activeFr'   => $activeFr,
                'totalEn'    => $totalEn,
                'pctEn'      => $totalFr > 0 ? round(100 * $totalEn / $totalFr) : 0,
                'totalEs'    => $totalEs,
                'pctEs'      => $totalFr > 0 ? round(100 * $totalEs / $totalFr) : 0,
                'totalProd'  => $totalProd,
                'prodRef'    => $prodRef,
            ],
            'sections' => [
                $this->sectionEssentiels($totalFr),
                $this->sectionMedias($totalFr),
                $this->sectionContenu($totalFr),
                $this->sectionMultilingue($totalFr, $totalEn, $totalEs),
                $this->sectionProducteurs($totalProd),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sections
    // ─────────────────────────────────────────────────────────────────────────

    private function sectionEssentiels(int $total): array
    {
        return [
            'title' => 'Fiches FR — Champs essentiels',
            'icon'  => '★',
            'checks' => [
                $this->frCheck('Titre manquant', 'error',
                    "(raison_sociale IS NULL OR raison_sociale = '') AND (titre_2 IS NULL OR titre_2 = '')", $total),
                $this->frCheck('Commune de départ absente', 'warning',
                    "commune_depart IS NULL OR commune_depart = ''", $total),
                $this->frCheck('Type d\'activité absent', 'warning',
                    "type IS NULL OR type = '' OR type = '[]'", $total),
                $this->frCheck('Difficulté non renseignée', 'warning',
                    "difficulte IS NULL OR difficulte = ''", $total),
                $this->frCheck('Distance manquante (null ou 0)', 'warning',
                    "distance_km IS NULL OR distance_km = 0", $total),
                $this->frCheck('Durée non renseignée', 'warning',
                    "duree IS NULL OR duree = ''", $total),
                $this->frCheck('Description principale absente', 'warning',
                    "descriptif IS NULL OR descriptif = ''", $total),
                $this->frCheck('Dénivélé positif manquant', 'info',
                    "denivele IS NULL OR denivele = 0", $total),
                $this->frCheck('Coordonnées GPS manquantes', 'warning',
                    "latitude IS NULL OR longitude IS NULL OR latitude = 0 OR longitude = 0", $total),
                $this->frCheck('Auteur sans correspondance dans la table producteur', 'error',
                    "auteur IS NULL OR auteur = '' OR auteur NOT IN (SELECT syndic_object_id FROM producteur)", $total),
            ],
        ];
    }

    private function sectionMedias(int $total): array
    {
        $cacheDir = Yii::getAlias(Yii::$app->params['pathCacheGmap'] ?? '@runtime/cache-gmap') . '/';
        $allIds   = $this->db->createCommand('SELECT id FROM fr')->queryColumn();

        $sansCarte = [];
        foreach ($allIds as $id) {
            if (!file_exists($cacheDir . $id . '.jpg')) {
                $sansCarte[] = $id;
            }
        }

        return [
            'title' => 'Fiches FR — Médias',
            'icon'  => '◉',
            'checks' => [
                $this->frCheck('Photo absente', 'warning',
                    "photo IS NULL OR photo = ''", $total),
                $this->frCheck('Fichier GPX absent', 'info',
                    "doc_gpx IS NULL OR doc_gpx = ''", $total),
                $this->frCheck('Fichier KML/KMZ absent', 'info',
                    "(doc_kml IS NULL OR doc_kml = '') AND (doc_kmz IS NULL OR doc_kmz = '')", $total),
                $this->frCheck('Balisage couleur absent', 'info',
                    "balisage_couleur IS NULL OR balisage_couleur = ''", $total),
                $this->frCheck('Fichier balisage absent', 'info',
                    "balisage_fichier IS NULL OR balisage_fichier = ''", $total),
                [
                    'label'    => 'Carte statique (cache JPG) manquante',
                    'severity' => 'info',
                    'count'    => count($sansCarte),
                    'pct'      => $total > 0 ? round(100 * count($sansCarte) / $total, 1) : 0,
                    'sample'   => array_slice($sansCarte, 0, 5),
                ],
            ],
        ];
    }

    private function sectionContenu(int $total): array
    {
        return [
            'title' => 'Fiches FR — Contenu détaillé',
            'icon'  => '≡',
            'checks' => [
                $this->frCheck('Étapes absentes', 'info',
                    "etapes IS NULL OR etapes = '' OR etapes = '[]'", $total),
                $this->frCheck('Points d\'intérêt absents', 'info',
                    "point_d_interet IS NULL OR point_d_interet = '' OR point_d_interet = '[]'", $total),
                $this->frCheck('Équipements non renseignés', 'info',
                    "equipement IS NULL OR equipement = '' OR equipement = '[]'", $total),
                $this->frCheck('Points d\'attention absents', 'info',
                    "point_d_attention IS NULL OR point_d_attention = '' OR point_d_attention = '[]'", $total),
                $this->frCheck('Parking non renseigné', 'info',
                    "parking IS NULL OR parking = ''", $total),
                $this->frCheck('Alerte active sans texte explicatif', 'warning',
                    "alerte = 1 AND (alerte_info IS NULL OR alerte_info = '')", $total),
                $this->frCheck('Locomotion non renseignée', 'info',
                    "locomotion IS NULL OR locomotion = '' OR locomotion = '[]'", $total),
            ],
        ];
    }

    private function sectionMultilingue(int $totalFr, int $totalEn, int $totalEs): array
    {
        $checks = [];

        // Couverture
        $checks[] = $this->bilanCheck(
            'Fiches FR sans équivalent EN',
            'info',
            "SELECT id FROM fr WHERE id NOT IN (SELECT id FROM en)",
            $totalFr
        );
        $checks[] = $this->bilanCheck(
            'Fiches FR sans équivalent ES',
            'info',
            "SELECT id FROM fr WHERE id NOT IN (SELECT id FROM es)",
            $totalFr
        );
        $checks[] = $this->bilanCheck(
            'Fiches EN orphelines (sans FR)',
            'warning',
            "SELECT id FROM en WHERE id NOT IN (SELECT id FROM fr)",
            $totalEn
        );
        $checks[] = $this->bilanCheck(
            'Fiches ES orphelines (sans FR)',
            'warning',
            "SELECT id FROM es WHERE id NOT IN (SELECT id FROM fr)",
            $totalEs
        );

        // Champs non traduits en EN
        if ($totalEn > 0) {
            $checks[] = $this->tableCheck('en', 'Titre non traduit (EN)', 'info',
                "raison_sociale IS NULL OR raison_sociale = ''", $totalEn);
            $checks[] = $this->tableCheck('en', 'Description non traduite (EN)', 'info',
                "descriptif IS NULL OR descriptif = ''", $totalEn);
        }

        // Champs non traduits en ES
        if ($totalEs > 0) {
            $checks[] = $this->tableCheck('es', 'Titre non traduit (ES)', 'info',
                "raison_sociale IS NULL OR raison_sociale = ''", $totalEs);
            $checks[] = $this->tableCheck('es', 'Description non traduite (ES)', 'info',
                "descriptif IS NULL OR descriptif = ''", $totalEs);
        }

        return [
            'title' => 'Cohérence multilingue',
            'icon'  => '⬡',
            'checks' => $checks,
        ];
    }

    private function sectionProducteurs(int $totalProd): array
    {
        $logoDir  = Yii::getAlias('@webroot') . '/producteur/';
        $refIds   = $this->db->createCommand(
            "SELECT DISTINCT auteur FROM fr WHERE auteur IS NOT NULL AND auteur != ''"
        )->queryColumn();

        $sansLogo = [];
        foreach ($refIds as $id) {
            if (!file_exists($logoDir . $id . '.png')) {
                $sansLogo[] = $id;
            }
        }
        $totalRef = count($refIds);

        return [
            'title' => 'Producteurs',
            'icon'  => '⌂',
            'checks' => [
                [
                    'label'    => 'Logo manquant (producteurs référencés dans les fiches)',
                    'severity' => 'warning',
                    'count'    => count($sansLogo),
                    'pct'      => $totalRef > 0 ? round(100 * count($sansLogo) / $totalRef, 1) : 0,
                    'sample'   => array_slice($sansLogo, 0, 5),
                    'note'     => "$totalRef producteurs référencés au total",
                ],
                $this->tableCheck('producteur', 'Raison sociale absente', 'error',
                    "raison_sociale IS NULL OR raison_sociale = ''", $totalProd),
                $this->tableCheck('producteur', 'Adresse absente', 'info',
                    "adresse_1 IS NULL OR adresse_1 = ''", $totalProd),
                $this->tableCheck('producteur', 'Téléphone absent', 'info',
                    "telephone IS NULL OR telephone = ''", $totalProd),
                $this->tableCheck('producteur', 'URL absente', 'info',
                    "url IS NULL OR url = ''", $totalProd),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function frCheck(string $label, string $severity, string $where, int $total): array
    {
        return $this->tableCheck('fr', $label, $severity, $where, $total);
    }

    private function tableCheck(string $table, string $label, string $severity, string $where, int $total): array
    {
        $idCol  = $table === 'producteur' ? 'syndic_object_id' : 'id';
        $count  = (int)$this->db->createCommand("SELECT COUNT(*) FROM `$table` WHERE $where")->queryScalar();
        $sample = $count > 0
            ? $this->db->createCommand("SELECT `$idCol` FROM `$table` WHERE $where LIMIT 5")->queryColumn()
            : [];

        return [
            'label'    => $label,
            'severity' => $severity,
            'count'    => $count,
            'pct'      => $total > 0 ? round(100 * $count / $total, 1) : 0,
            'sample'   => $sample,
        ];
    }

    private function bilanCheck(string $label, string $severity, string $sql, int $total): array
    {
        $count  = (int)$this->db->createCommand("SELECT COUNT(*) FROM ($sql) AS t")->queryScalar();
        $sample = $count > 0
            ? $this->db->createCommand("$sql LIMIT 5")->queryColumn()
            : [];

        return [
            'label'    => $label,
            'severity' => $severity,
            'count'    => $count,
            'pct'      => $total > 0 ? round(100 * $count / $total, 1) : 0,
            'sample'   => $sample,
        ];
    }
}
