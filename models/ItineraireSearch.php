<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

class ItineraireSearch extends Itineraire
{
    public string $langue = 'fr';

    public ?string $locomotion_val = null;
    public ?string $difficulte_val = null;

    public function rules(): array
    {
        return [
            [['id', 'titre_2', 'raison_sociale', 'commune_depart', 'auteur', 'locomotion_val', 'difficulte_val'], 'safe'],
            [['is_active'], 'boolean'],
            [['langue'], 'in', 'range' => ['fr', 'en', 'es']],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        Yii::$app->language = $this->langue;
        $query = Itineraire::find();

        $this->load($params);

        $query->andFilterWhere(['like', 'id', $this->id])
              ->andFilterWhere(['like', 'titre_2', $this->titre_2])
              ->andFilterWhere(['like', 'commune_depart', $this->commune_depart])
              ->andFilterWhere(['like', 'auteur', $this->auteur])
              ->andFilterWhere(['like', 'locomotion', $this->locomotion_val])
              ->andFilterWhere(['like', 'difficulte', $this->difficulte_val]);

        if ($this->is_active !== null && $this->is_active !== '') {
            $query->andWhere(['is_active' => $this->is_active]);
        }

        return new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 30],
            'sort'       => ['defaultOrder' => ['updated_at' => SORT_DESC]],
        ]);
    }
}
