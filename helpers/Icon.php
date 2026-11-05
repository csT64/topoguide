<?php

namespace app\helpers;

use Yii;
use yii\helpers\Html;

/**
 * Icônes Tabler (MIT, web/icons/LICENSE) auto-hébergées dans web/icons/,
 * rendues en SVG inline pour hériter de la couleur du texte (currentColor).
 * Même helper dans toutes les applications (charte graphique Tourisme 64).
 */
class Icon
{
    /** @var array<string, string> Cache des fichiers lus */
    private static $cache = [];

    /**
     * @param string $name Nom du fichier sans extension dans web/icons/ (ex. 'award')
     * @param array $options Attributs HTML de la balise <svg> ; 'class' s'ajoute à 'ic'
     */
    public static function svg(string $name, array $options = []): string
    {
        $name = basename($name);
        if (!isset(self::$cache[$name])) {
            $chemin = Yii::getAlias('@webroot/icons/' . $name . '.svg');
            if (!is_file($chemin)) {
                Yii::warning("Icône introuvable : {$name} ({$chemin})", __METHOD__);
                return '';
            }
            $svg = file_get_contents($chemin);
            // Taille et classe d'origine retirées : le CSS (.ic) pilote taille et couleur
            $svg = preg_replace('/\s(width|height)="24"/', '', $svg, 2);
            $svg = preg_replace('/\sclass="[^"]*"/', '', $svg, 1);
            self::$cache[$name] = $svg;
        }

        Html::addCssClass($options, 'ic');
        if (!isset($options['aria-label'])) {
            $options['aria-hidden'] = 'true';
        }

        return preg_replace('/<svg/', '<svg' . Html::renderTagAttributes($options), self::$cache[$name], 1);
    }
}
