<?php

namespace app\helpers;

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Menu déroulant de la barre de navigation — charte graphique Tourisme 64.
 *
 * Bouton natif <button> (et non <a role="button">), comme le recommande Bootstrap 5.3 ;
 * aria-expanded est mis à jour par Bootstrap. Une entrée peut être un lien
 * ['label' => …, 'url' => …] ou une chaîne HTML déjà prête (ex. Bouton::post(…, ['class' => 'dropdown-item'])).
 * À passer comme élément « chaîne » à yii\bootstrap5\Nav (encodeLabels => false).
 */
class Menu
{
    public static function deroulant(string $libelle, array $items): string
    {
        $lignes = [];
        foreach ($items as $item) {
            $lignes[] = '<li>' . (is_string($item)
                ? $item
                : Html::a($item['label'], Url::to($item['url']), ['class' => 'dropdown-item'])) . '</li>';
        }
        return '<li class="nav-item dropdown">'
            . Html::button($libelle, ['type' => 'button', 'class' => 'nav-link dropdown-toggle', 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false'])
            . '<ul class="dropdown-menu">' . implode('', $lignes) . '</ul></li>';
    }
}
