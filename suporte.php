<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();
$uid = usuario_logado();
$ok = false; $erros = [];

$CATEGORIAS = ['Problema técnico', 'Dúvida', 'Sugestão'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'abrir';

    if ($acao === 'encerrar' || $acao === 'reabrir') {
        $cid = (int) ($_POST['chamado_id'] ?? 0);
        // Confirma que o chamado é do próprio usuário
        $st = db()->prepare('SELECT status FROM chamados_suporte WHERE id=? AND usuario_id=?');
        $st->execute([$cid, $uid]);
        $atual = $st->fetch();
        if ($atual) {
            if ($acao === 'encerrar' && $atual['status'] === 'Resolvido') {
                db()->prepare("UPDATE chamados_suporte SET status='Encerrado' WHERE id=?")->execute([$cid]);
                $ok = true;
            } elseif ($acao === 'reabrir' && in_array($atual['status'], ['Resolvido','Encerrado'], true)) {
                db()->prepare("UPDATE chamados_suporte SET status='Aberto' WHERE id=?")->execute([$cid]);
                $ok = true;
            }
        }
    } else { // abrir novo chamado
        $categoria = trim($_POST['categoria'] ?? '');
        $assunto   = trim($_POST['assunto'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if (!in_array($categoria, $CATEGORIAS, true)) $erros[] = 'Selecione uma categoria válida.';
        if ($assunto === '')   $erros[] = 'Informe o assunto.';
        if ($descricao === '') $erros[] = 'Descreva o chamado.';

        if (!$erros) {
            db()->prepare(
                'INSERT INTO chamados_suporte (usuario_id, categoria, assunto, descricao) VALUES (?,?,?,?)'
            )->execute([$uid, $categoria, $assunto, $descricao]);
            $ok = true;
        }
    }
}

$stmt = db()->prepare('SELECT * FROM chamados_suporte WHERE usuario_id = ? ORDER BY criado_em DESC');
$stmt->execute([$uid]);
$chamados = $stmt->fetchAll();

$titulo = 'Suporte';
$pagina = 'suporte';
require __DIR__ . '/includes/header.php';
?>
<h1>Suporte</h1>
<p class="muted">Abra um chamado para a equipe técnica. Acompanhe o status abaixo.</p>

<?php if ($ok): ?>
  <div class="alerta alerta--ok">Chamado aberto com sucesso! A equipe técnica responderá em breve.</div>
<?php endif; ?>
<?php if ($erros): ?>
  <div class="alerta alerta--erro"><?= implode('<br>', array_map('e', $erros)) ?></div>
<?php endif; ?>

<div class="grade grade--2">
  <div class="card">
    <h3>Abrir chamado</h3>
    <form method="post">
      <div class="campo">
        <label for="categoria">Categoria</label>
        <select id="categoria" name="categoria" required>
          <option value="">Selecione</option>
          <?php foreach ($CATEGORIAS as $c): ?>
            <option value="<?= e($c) ?>"><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label for="assunto">Assunto</label>
        <input type="text" id="assunto" name="assunto" required>
      </div>
      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" required></textarea>
      </div>
      <button type="submit" class="btn btn--block">Enviar chamado</button>
    </form>
  </div>

  <div>
    <h3>Meus chamados</h3>
    <?php if (!$chamados): ?>
      <div class="card vazio">Você ainda não abriu chamados.</div>
    <?php else: ?>
      <?php foreach ($chamados as $ch):
        $cls = ['Aberto'=>'pendente','Em Progresso'=>'pendente','Resolvido'=>'aceito','Encerrado'=>'rejeitado'][$ch['status']] ?? 'pendente';
      ?>
        <div class="card" style="padding:1.1rem 1.2rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
            <strong>#<?= (int)$ch['id'] ?> · <?= e($ch['assunto']) ?></strong>
            <span class="badge badge--<?= $cls ?>"><?= e($ch['status']) ?></span>
          </div>
          <div class="muted" style="font-size:.85rem;margin:.25rem 0 .5rem;">
            <?= e($ch['categoria']) ?> · <?= e(date('d/m/Y H:i', strtotime($ch['criado_em']))) ?>
          </div>
          <p class="perfil-card__bio" style="margin:0;"><?= e($ch['descricao']) ?></p>

          <?php if (!empty($ch['resposta'])): ?>
            <div style="margin-top:.7rem;padding:.7rem .85rem;background:var(--bone-2);border-radius:10px;">
              <strong style="font-size:.9rem;">Resposta da equipe:</strong>
              <p style="margin:.3rem 0 0;font-size:.92rem;white-space:pre-wrap;"><?= e($ch['resposta']) ?></p>
            </div>
          <?php endif; ?>

          <?php if (in_array($ch['status'], ['Resolvido','Encerrado'], true)): ?>
            <div class="acoes-card" style="margin-top:.7rem;">
              <?php if ($ch['status'] === 'Resolvido'): ?>
                <form method="post"><input type="hidden" name="acao" value="encerrar"><input type="hidden" name="chamado_id" value="<?= (int)$ch['id'] ?>"><button class="btn btn--sm">Confirmar resolução</button></form>
              <?php endif; ?>
              <form method="post"><input type="hidden" name="acao" value="reabrir"><input type="hidden" name="chamado_id" value="<?= (int)$ch['id'] ?>"><button class="btn btn--ghost btn--sm">Reabrir</button></form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
