<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $lines */
/** @var string $logFile */
$this->title = 'Log des erreurs';
?>
<h2>Log des erreurs
  <?= Html::a('Vider', Url::to(['/admin/log/vider']), [
      'class' => 'btn btn-danger btn-sm',
      'data-method' => 'post',
      'data-confirm' => 'Vider le fichier log ?',
  ]) ?>
</h2>

<p>
  Fichier : <code><?= Html::encode($logFile) ?></code> —
  <strong><?= count($lines) ?></strong> ligne(s)
  <input type="text" id="log-filter" class="form-control input-sm" placeholder="Filtrer..." style="display:inline-block;width:200px;margin-left:10px;">
</p>

<?php if (empty($lines)): ?>
  <div class="alert alert-success">Aucune erreur.</div>
<?php else: ?>
<pre id="log-content" style="max-height:600px;overflow:auto;font-size:11px;background:#1e1e1e;color:#d4d4d4;padding:12px;"><?php
foreach ($lines as $line):
    $class = '';
    if (str_contains($line, 'Exception') || str_contains($line, 'Error')) $class = 'color:#f48771;';
    elseif (str_contains($line, 'producteur') || str_contains($line, 'WARNING')) $class = 'color:#dcdcaa;';
    elseif (str_contains($line, '[8]') || str_contains($line, 'Notice')) $class = 'color:#b5cea8;';
    echo '<span style="' . $class . '">' . Html::encode($line) . "</span>\n";
endforeach;
?></pre>
<?php endif; ?>

<script>
document.getElementById('log-filter').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  document.querySelectorAll('#log-content span').forEach(function(span) {
    span.style.display = span.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});
</script>
