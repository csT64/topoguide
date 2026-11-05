<?php

namespace app\widgets;

/**
 * Liste accessible — charte graphique Tourisme 64 (RGAA, thème 5).
 *
 *  - titre du tableau en <caption> natif (propriété $caption, obligatoire ; masqué visuellement
 *    par défaut quand un titre visible le précède déjà : captionOptions => [] pour l'afficher) ;
 *  - colonnes triables : le sens du tri est écrit en texte (DataColumn) ;
 *  - tableau dans .table-responsive, pagination Bootstrap 5, classes de la charte.
 */
class GridView extends \yii\grid\GridView
{
    public $dataColumnClass = DataColumn::class;
    public $tableOptions = ['class' => 'table table-hover table-sm align-middle'];
    public $captionOptions = ['class' => 'visually-hidden'];
    public $layout = "{summary}\n<div class=\"table-responsive\">{items}</div>\n{pager}";
    public $pager = ['class' => LinkPager::class];

    public function init()
    {
        if ($this->caption === null || $this->caption === '') {
            throw new \yii\base\InvalidConfigException('GridView : la propriété « caption » (titre du tableau) est obligatoire.');
        }
        parent::init();
    }
}
