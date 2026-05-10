<?php
use yii\helpers\Html;
use app\assets\AdminAsset;

/** @var yii\web\View $this */
/** @var string $content */

AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::encode($this->title) ?> — Topoguide Admin</title>
<?= $this->head() ?>
<style>
  body { padding-top: 60px; }
  .navbar-brand { font-weight: bold; }
  .flash-messages { margin-top: 10px; }
</style>
</head>
<body>
<?php $this->beginBody() ?>

<nav class="navbar navbar-inverse navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="<?= \yii\helpers\Url::to(['/admin/default/index']) ?>">Topoguide Admin</a>
    </div>
    <ul class="nav navbar-nav">
      <li><?= Html::a('Tableau de bord', ['/admin/default/index']) ?></li>
      <li><?= Html::a('Itinéraires', ['/admin/itineraire/index']) ?></li>
      <li><?= Html::a('Producteurs', ['/admin/producteur/index']) ?></li>
      <li><?= Html::a('Villes', ['/admin/ville/index']) ?></li>
      <li><?= Html::a('Cartes', ['/admin/carte/index']) ?></li>
      <li><?= Html::a('Logs', ['/admin/log/index']) ?></li>
    </ul>
    <ul class="nav navbar-nav navbar-right">
      <?php if (\Yii::$app->user->isGuest): ?>
      <li><?= Html::a('Connexion', ['/admin/default/login']) ?></li>
      <?php else: ?>
      <li><a><?= Html::encode(\Yii::$app->user->identity->username) ?></a></li>
      <li>
        <?= Html::beginForm(['/admin/default/logout'], 'post') ?>
        <?= Html::submitButton('Déconnexion', ['class' => 'btn btn-link navbar-btn']) ?>
        <?= Html::endForm() ?>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<div class="container-fluid">
  <div class="flash-messages">
    <?php foreach (\Yii::$app->session->getAllFlashes() as $type => $messages): ?>
      <?php foreach ((array)$messages as $msg): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?>">
          <?= Html::encode($msg) ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
