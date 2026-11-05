<?php

namespace app\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Barre de navigation — charte graphique Tourisme 64.
 * Le bouton qui ouvre le menu replié porte un texte masqué visuellement (nom natif) au
 * lieu d'un aria-label ; aria-controls / aria-expanded restent gérés par Bootstrap.
 */
class NavBar extends \yii\bootstrap5\NavBar
{
    /** @var string Nom du bouton du menu replié */
    public $texteBoutonMenu = 'Menu';

    protected function renderToggleButton(): string
    {
        if ($this->collapseOptions === false) {
            return parent::renderToggleButton();
        }
        $options = $this->togglerOptions;
        Html::addCssClass($options, ['widget' => 'navbar-toggler']);
        return Html::button(
            '<span class="navbar-toggler-icon"></span><span class="visually-hidden">' . Html::encode($this->texteBoutonMenu) . '</span>',
            ArrayHelper::merge($options, [
                'type' => 'button',
                'data' => ['bs-toggle' => 'collapse', 'bs-target' => '#' . $this->collapseOptions['id']],
                'aria' => ['controls' => $this->collapseOptions['id'], 'expanded' => 'false'],
            ])
        );
    }
}
