<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var app\models\Producteur $model */
/** @var yii\web\View $this */

$this->title = $model->raison_sociale ?: $model->syndic_object_id;
$this->params['breadcrumbs'][] = ['label' => 'Producteurs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="producteur-view">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="page-header-actions">
            <?= Html::a(Icon::svg('pencil') . ' Modifier', ['update', 'id' => $model->syndic_object_id], ['class' => 'btn btn-warning']) ?>
            <?= Bouton::post(
                Icon::svg('trash') . ' Supprimer',
                ['delete', 'id' => $model->syndic_object_id],
                ['class' => 'btn btn-danger', 'data-confirm' => 'Supprimer ce producteur ?']
            ) ?>
            <?= Html::a('Retour', ['index'], ['class' => 'btn btn-link']) ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Coordonnées</h2>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'syndic_object_id', 'raison_sociale',
                        'adresse_1', 'adresse_2', 'adresse_3',
                        'code_postal', 'commune',
                        'telephone', 'url:url',
                        'is_active:boolean',
                    ],
                ]) ?>
            </section>
        </div>
        <div class="col-md-6">
            <section class="panel">
                <h2 class="section-title">Logo</h2>
                <?php if ($model->hasLogo()): ?>
                    <img src="<?= Html::encode($model->getLogoUrl()) ?>"
                         style="max-width:300px;max-height:200px;border:1px solid var(--color-border);padding:8px;border-radius:4px">
                    <p class="texte-meta mt-2 mb-0"><?= Html::encode($model->getLogoFilename()) ?></p>
                <?php else: ?>
                    <p class="text-body-secondary">Aucun logo — <?= Html::a('Ajouter via Modifier', ['update', 'id' => $model->syndic_object_id]) ?></p>
                <?php endif ?>
            </section>
        </div>
    </div>
</div>
