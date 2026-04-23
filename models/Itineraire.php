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
            [['id'], 'string', 'max' => 32],
            [['titre_2', 'raison_sociale', 'commune_depart', 'commune_arrivee'], 'string'],
            [['distance_km', 'denivele', 'denivele_negatif_cumule'], 'number'],
            [['latitude', 'longitude'], 'number'],
            [['descriptif', 'descriptif_mobile'], 'string'],
            [['photo', 'etapes', 'difficulte', 'type', 'equipement',
              'point_d_interet', 'point_d_attention', 'locomotion', 'duree'], 'safe'],
            [['doc_gpx', 'doc_kml', 'doc_kmz'], 'string'],
            [['auteur', 'code_insee'], 'string'],
            [['is_active'], 'boolean'],
            [['created_at', 'updated_at', 'deleted_at'], 'safe'],
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

    public function getTitle(): string
    {
        return $this->titre_2 ?: ($this->raison_sociale ?: '');
    }

    public function getProducteurId(): string
    {
        return substr($this->auteur ?? '', 0, 16) ?: 'ORGAQU064FS00001';
    }

    public function getDifficulteVal(): string
    {
        $d = json_decode($this->difficulte ?? '', true);
        return $d['val'] ?? '';
    }

    public function getTypeVal(): string
    {
        $d = json_decode($this->type ?? '', true);
        return $d['val'] ?? '';
    }

    public function getDureeVal(): string
    {
        $d = json_decode($this->duree ?? '', true);
        return $d['val'] ?? '';
    }

    public function getPhotos(): array
    {
        return json_decode($this->photo ?? '', true) ?: [];
    }

    public function getEtapes(): array
    {
        return json_decode($this->etapes ?? '', true) ?: [];
    }

    public function getPoi(): array
    {
        return json_decode($this->point_d_interet ?? '', true) ?: [];
    }

    public function getEquipements(): array
    {
        return json_decode($this->equipement ?? '', true) ?: [];
    }

    public function getAttentions(): array
    {
        return json_decode($this->point_d_attention ?? '', true) ?: [];
    }

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
