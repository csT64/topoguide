<?php

namespace app\widgets;

/**
 * Pagination — charte graphique Tourisme 64.
 * Les liens « précédent / suivant » de Bootstrap ne contiennent qu'une flèche masquée
 * (aria-hidden) : on leur donne un nom en texte masqué visuellement.
 */
class LinkPager extends \yii\bootstrap5\LinkPager
{
    public $prevPageLabel = '<span aria-hidden="true">&laquo;</span><span class="visually-hidden">Page précédente</span>';
    public $nextPageLabel = '<span aria-hidden="true">&raquo;</span><span class="visually-hidden">Page suivante</span>';
    public $options = ['aria-label' => 'Pagination'];
}
