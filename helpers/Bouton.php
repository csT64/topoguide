<?php

namespace app\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Action qui modifie des données (POST) — charte graphique Tourisme 64.
 *
 * Rend un vrai <button> dans un petit <form method="post"> (avec le jeton CSRF), au lieu
 * d'un lien <a data-method="post"> : élément natif, utilisable au clavier et sans
 * JavaScript, annoncé comme un bouton. Une confirmation éventuelle passe par data-confirm
 * (yii.js) ; sans JavaScript, l'action part sans confirmation.
 *
 * Ne pas l'utiliser à l'intérieur d'un autre <form> (formulaires imbriqués interdits) :
 * dans ce cas, un <button type="submit" formaction="…"> du formulaire englobant.
 */
class Bouton
{
    /**
     * @param string $libelle contenu du bouton (HTML : pictos Icon::svg() + texte)
     * @param array|string $url route Yii ou URL
     * @param array $options attributs du <button> (class, data-confirm, title…)
     * @param array $params champs cachés envoyés avec l'action (nom => valeur ou tableau)
     */
    public static function post(string $libelle, $url, array $options = [], array $params = []): string
    {
        $options['type'] = 'submit';
        $html = Html::beginForm(Url::to($url), 'post', ['class' => 'form-action']);
        foreach ($params as $nom => $valeur) {
            foreach ((array) $valeur as $v) {
                $html .= Html::hiddenInput(is_array($valeur) ? $nom . '[]' : $nom, $v);
            }
        }
        return $html . Html::tag('button', $libelle, $options) . Html::endForm();
    }
}
