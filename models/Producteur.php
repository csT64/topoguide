<?php

namespace app\models;

use yii\db\ActiveRecord;

class Producteur extends ActiveRecord
{
    public ?string $logoFile = null;

    public static function tableName(): string
    {
        return 'producteur';
    }

    public static function primaryKey(): array
    {
        return ['syndic_object_id'];
    }

    public function rules(): array
    {
        return [
            [['syndic_object_id'], 'required'],
            [['syndic_object_id', 'raison_sociale', 'adresse_1', 'adresse_2', 'adresse_3', 'url'], 'string', 'max' => 255],
            [['code_postal'], 'string', 'max' => 10],
            [['commune'], 'string', 'max' => 64],
            [['telephone'], 'string', 'max' => 20],
            [['source_group_id'], 'integer'],
            [['is_active'], 'boolean'],
            [['extraction_date', 'deleted_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }
}
