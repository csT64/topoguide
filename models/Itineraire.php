<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Itineraire extends ActiveRecord
{
    public static function tableName(): string
    {
        return Yii::$app->language; // 'fr', 'en' ou 'es'
    }

    public function rules(): array
    {
        return [
            [['id'], 'required'],
            [['id', 'auteur', 'code_insee', 'syndic_object_id'], 'string', 'max' => 255],
            [['titre_2', 'raison_sociale', 'commune_depart', 'commune_arrivee'], 'string', 'max' => 255],
            [['parking', 'balisage_couleur'], 'string', 'max' => 255],
            [['duree_txt'], 'string', 'max' => 50],
            [['distance_km', 'denivele', 'denivele_negatif_cumule'], 'number'],
            [['latitude', 'longitude'], 'number'],
            [['descriptif', 'descriptif_mobile', 'alerte_info', 'video'], 'string'],
            [['doc_gpx', 'doc_kml', 'doc_kmz',
              'doc_pdf_fr', 'doc_pdf_en', 'doc_pdf_es', 'doc_pdf_eu',
              'balisage_fichier'], 'string'],
            [['photo', 'etapes', 'difficulte', 'type', 'typologie', 'locomotion', 'duree',
              'equipement', 'point_d_interet', 'point_d_attention',
              'label', 'thematique', 'type_cible', 'type_public',
              'zonemontagne', 'qualification', 'paysage_dominant'], 'safe'],
            [['homologue_ffr', 'alerte', 'is_active'], 'boolean'],
            [['created_at', 'updated_at', 'deleted_at', 'date_maj', 'extraction_date'], 'safe'],
        ];
    }

    public function getProducteur(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Producteur::class, ['id' => 'auteur']);
    }

    public function getVille(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Ville::class, ['ville_code' => 'code_insee']);
    }

    // --- Helpers lecture ---

    public function getTitle(): string
    {
        return $this->titre_2 ?: ($this->raison_sociale ?: '');
    }

    public function getProducteurId(): string
    {
        return substr($this->auteur ?? '', 0, 16) ?: 'ORGAQU064FS00001';
    }

    public function getDept(): string
    {
        return substr($this->id ?? '', 7, 2);
    }

    // Décode un champ JSON — compatible format plat ["val"] et riche [{"ThesLibelle":"..."}]
    private function decodeJson(string $field): array
    {
        return json_decode($this->$field ?? '', true) ?: [];
    }

    public function getDifficulteVal(): string
    {
        $d = $this->decodeJson('difficulte');
        return !empty($d[0]) ? (is_array($d[0]) ? ($d[0]['ThesLibelle'] ?? '') : (string)$d[0]) : '';
    }

    public function getTypeVal(): string
    {
        $d = $this->decodeJson('type');
        return !empty($d[0]) ? (is_array($d[0]) ? ($d[0]['ThesLibelle'] ?? '') : (string)$d[0]) : '';
    }

    public function getDureeVal(): string
    {
        $d = $this->decodeJson('duree');
        return !empty($d[0]) ? (is_array($d[0]) ? ($d[0]['Tempsdeparcours'] ?? '') : (string)$d[0]) : '';
    }

    public function getBoucleVal(): string
    {
        foreach ($this->decodeJson('typologie') as $t) {
            $v = is_array($t) ? ($t['ThesLibelle'] ?? '') : (string)$t;
            if (!empty($v) && stripos($v, 'Boucle') !== false) {
                return 'Boucle';
            }
        }
        return '';
    }

    public function getPhotos(): array    { return $this->decodeJson('photo'); }
    public function getEtapes(): array    { return $this->decodeJson('etapes'); }
    public function getPoi(): array       { return $this->decodeJson('point_d_interet'); }
    public function getEquipements(): array { return $this->decodeJson('equipement'); }
    public function getAttentions(): array  { return $this->decodeJson('point_d_attention'); }

    public function hasCarteCache(): bool
    {
        return file_exists(Yii::$app->params['pathCacheGmap'] . '/' . $this->id . '.jpg');
    }

    public function getCarteCacheDate(): ?string
    {
        $path = Yii::$app->params['pathCacheGmap'] . '/' . $this->id . '.jpg';
        return file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null;
    }
}
