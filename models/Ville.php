<?php

namespace app\models;

use yii\db\ActiveRecord;

class Ville extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'ville';
    }

    public function rules(): array
    {
        return [
            [['ville_id'], 'required'],
            [['ville_id', 'ville_code'], 'string', 'max' => 16],
            [['default_zoom'], 'integer', 'min' => 1, 'max' => 18],
        ];
    }
}
