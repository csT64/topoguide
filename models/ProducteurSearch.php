<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class ProducteurSearch extends Producteur
{
    public function rules(): array
    {
        return [
            [['id', 'raison_sociale', 'commune'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Producteur::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder' => ['raison_sociale' => SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'id', $this->id])
              ->andFilterWhere(['like', 'raison_sociale', $this->raison_sociale])
              ->andFilterWhere(['like', 'commune', $this->commune]);

        return $dataProvider;
    }
}
