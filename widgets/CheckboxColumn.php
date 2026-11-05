<?php

namespace app\widgets;

use Closure;
use yii\helpers\Html;

/**
 * Colonne de cases de sélection avec étiquettes natives (<label> masquée visuellement),
 * y compris la case « tout sélectionner ».
 *
 * $libelle : fonction ($model) => texte de l'étiquette de la ligne (ex. « Sélectionner Gîtes de France »).
 */
class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    /** @var Closure|null fn ($model, $key, $index) => string */
    public $libelle;

    public $headerOptions = ['style' => 'width: 2.5rem'];

    protected function renderHeaderCellContent()
    {
        if ($this->header !== null || !$this->multiple) {
            return parent::renderHeaderCellContent();
        }
        $options = ['class' => 'select-on-check-all', 'label' => '<span class="visually-hidden">Tout sélectionner</span>'];
        if (isset($this->checkboxOptions['form']) && !$this->checkboxOptions instanceof Closure) {
            $options['form'] = $this->checkboxOptions['form'];
        }
        return Html::checkbox($this->getHeaderCheckBoxName(), false, $options);
    }

    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->content !== null || !$this->libelle instanceof Closure) {
            return parent::renderDataCellContent($model, $key, $index);
        }
        $options = $this->checkboxOptions instanceof Closure
            ? call_user_func($this->checkboxOptions, $model, $key, $index, $this)
            : $this->checkboxOptions;
        if (!isset($options['value'])) {
            $options['value'] = is_array($key) ? \yii\helpers\Json::encode($key) : $key;
        }
        if ($this->cssClass !== null) {
            Html::addCssClass($options, $this->cssClass);
        }
        $options['label'] = '<span class="visually-hidden">' . Html::encode(call_user_func($this->libelle, $model, $key, $index)) . '</span>';
        $options['labelOptions'] = ['class' => 'mb-0'];
        return Html::checkbox($this->name, !empty($options['checked']), $options);
    }
}
