<?php

namespace app\helpers;

use Yii;
use yii\helpers\Html;

/**
 * Logo institutionnel (Tourisme 64 / AaDT) — charte graphique Tourisme 64.
 *
 * Deux fichiers dans web/images/logo/ (fournis le 2026-11-05, PNG 481 × 250 px) :
 *   logo-negatif.svg  version blanche, pour fond sombre (barre de navigation, panneau de connexion,
 *                     pied de page en thème sombre)
 *   logo-positif.svg  version couleur, pour fond clair (pied de page en thème clair)
 * Tant qu'un fichier manque, le gabarit affiche le picto et le nom de l'application.
 */
class Marque
{
    public const NEGATIF = 'logo-negatif';
    public const POSITIF = 'logo-positif';

    /** Texte alternatif : le texte du logo (jamais « logo ») */
    public const ALT = 'Béarn Pyrénées Pays Basque — Tourisme 64';

    public static function existe(string $version): bool
    {
        return self::fichier($version) !== null;
    }

    /**
     * Balise <img> du logo, ou chaîne vide si le fichier est absent.
     *
     * @param array $options attributs HTML (class, alt…) ; alt="" si le nom de l'appli est déjà écrit à côté
     */
    public static function logo(string $version, array $options = []): string
    {
        $fichier = self::fichier($version);
        if ($fichier === null) {
            return '';
        }
        $options += ['alt' => self::ALT];
        return Html::img(Yii::getAlias('@web/images/logo/') . basename($fichier) . '?v=' . filemtime($fichier), $options);
    }

    private static function fichier(string $version): ?string
    {
        foreach (['svg', 'png'] as $ext) {
            $chemin = Yii::getAlias('@webroot/images/logo/' . basename($version) . '.' . $ext);
            if (is_file($chemin)) {
                return $chemin;
            }
        }
        return null;
    }
}
