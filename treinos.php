<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();
$uid = usuario_logado();

$stmt = db()->prepare(
    "SELECT th.*, t.nome AS treino_nome, t.tipo, t.nivel, t.descricao, t.duracao_minutos,
            me.nome AS atendente
     FROM treinos_habilitados th
     JOIN treinos t ON t.id = th.treino_id
     LEFT JOIN membros_equipe me ON me.id = th.atendente_id
     WHERE th.usuario_id = ?
     ORDER BY th.status='Ativo' DESC, th.criado_em DESC"
);
$stmt->execute([$uid]);
$treinos = $stmt->fetchAll();

$titulo = 'Meus treinos';
$pagina = 'treinos';
require __DIR__ . '/includes/header.php';
?>
<h1>Meus treinos</h1>
<p class="muted">Treinos habilitados pela equipe técnica conforme seu perfil.</p>

<?php if (!$treinos): ?>
  <div class="card vazio">
    <h3>Nenhum treino habilitado ainda</h3>
    <p>A equipe técnica habilitará treinos adequados ao seu objetivo.</p>
  </div>
<?php else: ?>
  <div class="grade grade--auto">
    <?php foreach ($treinos as $t): ?>
      <div class="card perfil-card">
        <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:center;">
          <div class="perfil-card__nome"><?= e($t['treino_nome']) ?></div>
          <span class="badge badge--<?= $t['status']==='Ativo'?'aceito':'rejeitado' ?>"><?= e($t['status']) ?></span>
        </div>
        <div class="tags">
          <span class="tag"><?= e($t['tipo']) ?></span>
          <span class="tag"><?= e($t['nivel']) ?></span>
          <?php if ($t['duracao_minutos']): ?><span class="tag"><?= (int)$t['duracao_minutos'] ?> min</span><?php endif; ?>
        </div>
        <?php if ($t['descricao']): ?><p class="perfil-card__bio"><?= e($t['descricao']) ?></p><?php endif; ?>
        <?php if ($t['observacoes']): ?>
          <p style="font-size:.9rem;"><strong>Orientação:</strong> <?= e($t['observacoes']) ?></p>
        <?php endif; ?>
        <div class="muted" style="font-size:.82rem;">
          <?php if ($t['data_fim']): ?>Válido até <?= e(date('d/m/Y', strtotime($t['data_fim']))) ?><?php endif; ?>
          <?php if ($t['atendente']): ?> · por <?= e($t['atendente']) ?><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
