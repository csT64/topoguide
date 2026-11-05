<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title ? $this->title . ' — ' . Yii::$app->name : Yii::$app->name) ?></title>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 p-2 bg-body z-3" href="#main">Aller au contenu</a>

<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1" tabindex="-1">
    <div class="container">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget([
                // Nom du <nav> : distingue le fil d'Ariane de la navigation principale
                'navOptions' => ['aria-label' => 'Fil d\'Ariane'],
                'homeLink' => ['label' => 'Tableau de bord', 'url' => Yii::$app->homeUrl],
                'links' => $this->params['breadcrumbs'],
            ]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<?= $this->render('_footer') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
