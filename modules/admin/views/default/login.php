<?php

/**
 * Page de connexion — standalone (layout false).
 *
 * @var yii\web\View $this
 * @var app\models\LoginForm $model
 */

use app\assets\AppAsset;
use app\helpers\Marque;
use app\widgets\ActiveForm;
use yii\bootstrap5\Html;

AppAsset::register($this);
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
$this->registerCsrfMetaTags();
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => Yii::getAlias('@web/favicon.svg')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode(Yii::$app->name) ?> — Connexion</title>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-body-tertiary">
<?php $this->beginBody() ?>

<div class="panel" style="width:100%;max-width:400px;padding:2rem;">
    <?php if (Marque::existe(Marque::NEGATIF) || Marque::existe(Marque::POSITIF)): ?>
        <div class="text-center mb-4">
            <?= Marque::logo(
                Marque::existe(Marque::POSITIF) ? Marque::POSITIF : Marque::NEGATIF,
                ['style' => 'max-height:60px']
            ) ?>
        </div>
    <?php endif ?>
    <h1 class="h4 text-center mb-4"><?= Html::encode(Yii::$app->name) ?></h1>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
        <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'autocomplete' => 'username']) ?>
        <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password']) ?>
        <?= $form->field($model, 'rememberMe')->checkbox(['label' => 'Rester connecté']) ?>
        <div class="d-grid mt-3">
            <?= Html::submitButton('Connexion', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
