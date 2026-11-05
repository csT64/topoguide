<?php

use app\helpers\Bouton;
use app\helpers\Icon;
use yii\helpers\Html;

/** @var array $lines */
/** @var string $logFile */

$this->title = 'Log des erreurs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="log-index">
    <div class="page-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="page-header-sub">
                Fichier : <code><?= Html::encode($logFile) ?></code> —
                <strong><?= count($lines) ?></strong> ligne(s)
            </p>
        </div>
        <div class="page-header-actions">
            <?= Bouton::post(
                Icon::svg('trash') . ' Vider',
                ['/admin/log/vider'],
                ['class' => 'btn btn-danger', 'data-confirm' => 'Vider le fichier log ?']
            ) ?>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label visually-hidden" for="log-filter">Filtrer les lignes</label>
        <input type="text" id="log-filter" class="form-control" placeholder="Filtrer les lignes…" style="max-width:300px">
    </div>

    <?php if (empty($lines)): ?>
        <div class="alert alert-success" role="status">Aucune erreur.</div>
    <?php else: ?>
        <pre id="log-content" style="max-height:600px;overflow:auto;font-size:11px;background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:4px"><?php
        foreach ($lines as $line):
            $style = '';
            if (str_contains($line, 'Exception') || str_contains($line, 'Error')) $style = 'color:#f48771';
            elseif (str_contains($line, 'WARNING')) $style = 'color:#dcdcaa';
            elseif (str_contains($line, '[8]') || str_contains($line, 'Notice')) $style = 'color:#b5cea8';
            echo '<span' . ($style ? ' style="' . $style . '"' : '') . '>' . Html::encode($line) . "</span>\n";
        endforeach;
        ?></pre>
    <?php endif ?>
</div>
<script>
document.getElementById('log-filter').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#log-content span').forEach(function(span) {
        span.style.display = span.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
