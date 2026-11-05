<?php

namespace app\widgets;

use Yii;
use yii\bootstrap5\Alert as BootstrapAlert;
use yii\bootstrap5\Widget;

/**
 * Affiche les messages flash de session (setFlash('success'|'danger'|'warning'|'info'|'error', ...)).
 * Charte graphique Tourisme 64 : role="alert" pour erreurs et avertissements, role="status" sinon.
 */
class Alert extends Widget
{
    /** @var array type de flash => classe d'alerte Bootstrap */
    public $alertTypes = [
        'error' => 'alert-danger',
        'danger' => 'alert-danger',
        'success' => 'alert-success',
        'info' => 'alert-info',
        'warning' => 'alert-warning',
    ];

    public function run()
    {
        $session = Yii::$app->session;
        if (!$session->getIsActive() && !$session->getHasSessionId()) {
            return;
        }

        foreach (array_keys($this->alertTypes) as $type) {
            foreach ((array) $session->getFlash($type) as $i => $message) {
                echo BootstrapAlert::widget([
                    'body' => $message,
                    'options' => array_merge($this->options, [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $this->alertTypes[$type],
                        // Message de statut (RGAA 7.5) : erreurs et avertissements annoncés tout de suite,
                        // succès et informations annoncés sans interrompre
                        'role' => in_array($type, ['error', 'danger', 'warning'], true) ? 'alert' : 'status',
                    ]),
                ]);
            }
            $session->removeFlash($type);
        }
    }
}
