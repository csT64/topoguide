<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\LoginForm $model */
$this->title = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Topoguide — Connexion</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<style>
  body { background: #f5f5f5; }
  .login-box { max-width: 380px; margin: 100px auto; background: #fff; padding: 30px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
</style>
</head>
<body>
<div class="login-box">
  <h3 class="text-center">Topoguide Admin</h3>
  <hr>
  <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
    <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => 'Identifiant']) ?>
    <?= $form->field($model, 'password')->passwordInput(['placeholder' => 'Mot de passe']) ?>
    <?= $form->field($model, 'rememberMe')->checkbox() ?>
    <div class="form-group">
      <?= Html::submitButton('Connexion', ['class' => 'btn btn-primary btn-block']) ?>
    </div>
  <?php ActiveForm::end(); ?>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
