<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class VilleSearch extends Ville
{
    public function rules(): array
    {
        return [
            [['ville_id', 'ville_code'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Ville::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder' => ['ville_code' => SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'ville_id', $this->ville_id])
              ->andFilterWhere(['like', 'ville_code', $this->ville_code]);

        return $dataProvider;
    }
}
