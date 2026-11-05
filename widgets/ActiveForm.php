<?php

namespace app\widgets;

/**
 * Formulaire accessible — charte graphique Tourisme 64 (RGAA, thème 11).
 *
 * À utiliser à la place de yii\bootstrap5\ActiveForm :
 *  - novalidate : la validation est faite par Yii (messages en français, reliés au champ),
 *    pas par les bulles du navigateur, alors que les champs portent l'attribut natif required ;
 *  - champs créés avec app\widgets\ActiveField (voir cette classe) ;
 *  - mentionObligatoire() : phrase à placer en tête de tout formulaire qui a des champs
 *    obligatoires (RGAA 11.10) ;
 *  - après une soumission refusée par la validation, le focus va au premier champ en erreur.
 */
class ActiveForm extends \yii\bootstrap5\ActiveForm
{
    public $fieldClass = ActiveField::class;

    /** Pas de marque verte « valide » sur les champs corrects (ni sur les champs facultatifs vides) */
    public $successCssClass = '';

    public function init(): void
    {
        if (!array_key_exists('novalidate', $this->options)) {
            $this->options['novalidate'] = true;
        }
        parent::init();
        // Après une soumission refusée, le focus va au premier champ en erreur (clavier, lecteur d'écran)
        $id = json_encode($this->options['id']);
        $this->getView()->registerJs("jQuery('#' + $id).on('afterValidate', function (e, messages, erreurs) {
    if (erreurs.length) { jQuery(this).find('[aria-invalid=\"true\"]').first().trigger('focus'); }
});");
    }

    /**
     * Phrase expliquant l'astérisque, à afficher avant le premier champ.
     */
    public function mentionObligatoire(): string
    {
        return '<p class="mention-obligatoire">Les champs suivis d\'un astérisque (<span class="obligatoire">*</span>) sont obligatoires.</p>';
    }
}
