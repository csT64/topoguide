<?php

namespace app\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Champ de formulaire accessible — charte graphique Tourisme 64 (RGAA, thème 11).
 *
 * Par rapport à yii\bootstrap5\ActiveField :
 *  - champ obligatoire : attribut natif required (au lieu de aria-required) et astérisque
 *    visible dans l'étiquette (expliqué par ActiveForm::mentionObligatoire()) ;
 *  - aide et message d'erreur reliés au champ par aria-describedby (pas d'équivalent natif) ;
 *  - aide rendue en .hint-block (composants.css) ;
 *  - groupes de boutons radio / cases (radioList, checkboxList) : <fieldset> + <legend>
 *    au lieu d'une <label> qui ne pointe sur aucun champ.
 * aria-invalid reste posé par Yii sur un champ en erreur (état sans équivalent natif ici).
 */
class ActiveField extends \yii\bootstrap5\ActiveField
{
    public $hintOptions = ['class' => 'hint-block', 'tag' => 'div'];

    /** @var bool Champ rendu en groupe (fieldset/legend) */
    private $groupe = false;

    public function init()
    {
        parent::init();
        $id = Html::getInputId($this->model, $this->attribute);
        // yii2-bootstrap5 réimpose « form-text text-muted » à la construction : on fixe la classe ici
        $this->hintOptions['class'] = 'hint-block';
        $this->hintOptions['id'] = $id . '-aide';
        $this->errorOptions['id'] = $id . '-erreur';
    }

    /**
     * Case à cocher : un libellé passé en option devient une vraie <label for> (yii2-bootstrap5
     * le rendrait en texte brut, sans étiquette associée).
     */
    public function checkbox($options = [], $enclosedByLabel = false): \yii\bootstrap5\ActiveField
    {
        $libelle = ArrayHelper::remove($options, 'label');
        parent::checkbox($options, $enclosedByLabel);
        if ($libelle !== null && !$enclosedByLabel) {
            $this->label($libelle);
        }
        return $this;
    }

    public function radioList($items, $options = []): \yii\bootstrap5\ActiveField
    {
        $this->groupe = true;
        // Le <fieldset> joue déjà le rôle de groupe : pas de role="radiogroup" en plus
        $options['role'] = false;
        if ($this->estObligatoire()) {
            $options['itemOptions']['required'] = true;
        }
        return parent::radioList($items, $options);
    }

    public function checkboxList($items, $options = []): \yii\bootstrap5\ActiveField
    {
        $this->groupe = true;
        $options['role'] = false;
        return parent::checkboxList($items, $options);
    }

    /**
     * required natif à la place de aria-required ; aria-invalid conservé.
     */
    protected function addAriaAttributes(&$options)
    {
        if ($this->addAriaAttributes && !$this->groupe && !isset($options['required']) && $this->estObligatoire()) {
            $options['required'] = true;
        }
        if ($this->addAriaAttributes && !isset($options['aria-invalid']) && $this->model->hasErrors(Html::getAttributeName($this->attribute))) {
            $options['aria-invalid'] = 'true';
        }
    }

    public function render($content = null): string
    {
        if ($this->groupe) {
            $this->options['tag'] = 'fieldset';
        }
        $html = parent::render($content);
        if ($content !== null) {
            return $html;
        }
        $id = $this->getInputId();
        $q = preg_quote($id, '/');

        // Étiquette (ou légende du groupe) : astérisque des champs obligatoires
        $etoile = $this->estObligatoire() ? ' <span class="obligatoire">*</span>' : '';
        if ($this->groupe) {
            $html = preg_replace('/<label([^>]*?)\sfor="' . $q . '"([^>]*)>(.*?)<\/label>/s', '<legend$1$2>$3' . $etoile . '</legend>', $html, 1);
        } elseif ($etoile !== '') {
            $html = preg_replace('/(<label[^>]*\sfor="' . $q . '"[^>]*>)(.*?)(<\/label>)/s', '$1$2' . $etoile . '$3', $html, 1);
        }

        // Aide et erreur reliées au champ (ou au groupe)
        $decrit = [];
        foreach ([$this->hintOptions['id'] ?? null, $this->errorOptions['id'] ?? null] as $idDesc) {
            if ($idDesc !== null && strpos($html, 'id="' . $idDesc . '"') !== false) {
                $decrit[] = $idDesc;
            }
        }
        if ($decrit) {
            $cible = $this->groupe ? '/<fieldset\b/' : '/(<(?:input|select|textarea)\b[^>]*\sid="' . $q . '")/';
            $remplacement = $this->groupe
                ? '<fieldset aria-describedby="' . implode(' ', $decrit) . '"'
                : '$1 aria-describedby="' . implode(' ', $decrit) . '"';
            $html = preg_replace($cible, $remplacement, $html, 1);
        }
        return $html;
    }

    private function estObligatoire(): bool
    {
        return $this->model->isAttributeRequired(Html::getAttributeName($this->attribute));
    }
}
