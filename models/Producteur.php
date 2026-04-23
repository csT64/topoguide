<?php

namespace app\models;

use yii\db\ActiveRecord;

class Producteur extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'producteur';
    }

    public function rules(): array
    {
        return [
            [['id'], 'required'],
            [['id', 'raison_sociale'], 'string', 'max' => 64],
            [['adresse_1', 'adresse_2', 'adresse_3'], 'string', 'max' => 128],
            [['code_postal'], 'string', 'max' => 10],
            [['commune'], 'string', 'max' => 64],
            [['telephone'], 'string', 'max' => 20],
            [['url'], 'string', 'max' => 255],
            [['logo'], 'string', 'max' => 255],
        ];
    }
}
