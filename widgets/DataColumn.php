<?php

namespace app\widgets;

use yii\helpers\Html;

/**
 * Colonne de liste dont le tri en cours est écrit en texte (masqué visuellement) dans
 * l'en-tête : la flèche CSS seule ne suffit pas (RGAA 1.1 / 3.1, information par la forme).
 */
class DataColumn extends \yii\grid\DataColumn
{
    protected function renderHeaderCellContent()
    {
        $contenu = parent::renderHeaderCellContent();
        $sort = $this->grid->dataProvider->getSort();
        if ($this->attribute !== null && $this->enableSorting && $sort !== false && $sort->hasAttribute($this->attribute)) {
            $ordre = $sort->getAttributeOrder($this->attribute);
            $etat = $ordre === SORT_ASC ? 'trié par ordre croissant' : ($ordre === SORT_DESC ? 'trié par ordre décroissant' : 'cliquer pour trier');
            $contenu = preg_replace('/<\/a>$/', '<span class="visually-hidden"> (' . $etat . ')</span></a>', $contenu, 1);
        }
        return $contenu;
    }
}
