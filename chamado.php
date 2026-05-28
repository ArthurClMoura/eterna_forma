<?php
require_once __DIR__ . '/includes/auth.php';
exigir_equipe();
$m = membro_atual();
$cats = categorias_do_membro($m);

$id = (int) ($_GET['id'] ?? 0);

function carregar_chamado(int $id): ?array {
    $st = db()->prepare(
        'SELECT c.*, u.nome AS desportista, u.email AS desportista_email, me.nome AS atendente_nome
         FROM chamados_suporte c
         JOIN usuarios u ON u.id = c.usuario_id
         LEFT JOIN membros_equipe me ON me.id = c.atendente_id
         WHERE c.id = ?'
    );
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

$ch = carregar_chamado($id);
if (!$ch) { http_response_code(404); exit('Chamado não encontrado.'); }

// Atendente só acessa chamados das suas categorias
if (!in_array($ch['categoria'], $cats, true)) {
    header('Location: painel.php'); exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'aceitar' && $ch['status'] === 'Aberto') {
        db()->prepare("UPDATE chamados_suporte SET status='Em Progresso', atendente_id=? WHERE id=?")
            ->execute([$m['id'], $id]);
        $msg = 'Chamado aceito — agora está "Em Progresso".';

    } elseif ($acao === 'responder') {
        $resposta = trim($_POST['resposta'] ?? '');
        $novoStatus = ($_POST['resolver'] ?? '') === '1' ? 'Resolvido' : 'Em Progresso';
        if ($resposta !== '') {
            db()->prepare(
                "UPDATE chamados_suporte
                 SET resposta=?, data_resposta=NOW(), status=?, atendente_id=COALESCE(atendente_id, ?)
                 WHERE id=?"
            )->execute([$resposta, $novoStatus, $m['id'], $id]);
            $msg = $novoStatus === 'Resolvido'
                 ? 'Resposta enviada e chamado marcado como Resolvido.'
                 : 'Resposta enviada ao desportista.';
        } else {
            $msg = 'Escreva uma resposta antes de enviar.';
        }

    } elseif ($acao === 'resolver') {
        db()->prepare("UPDATE chamados_suporte SET status='Resolvido' WHERE id=?")->execute([$id]);
        $msg = 'Chamado marcado como Resolvido.';

    } elseif ($acao === 'reatribuir' && $m['papel'] === 'Gestor') {
        $novo = (int) ($_POST['atendente_id'] ?? 0);
        if ($novo > 0) {
            db()->prepare("UPDATE chamados_suporte SET atendente_id=? WHERE id=?")->execute([$novo, $id]);
            $msg = 'Chamado reatribuído.';
        }
    }

    $ch = carregar_chamado($id); // recarrega
}

// Lista de atendentes para reatribuição (apenas gestor)
$membros = [];
if ($m['papel'] === 'Gestor') {
    $membros = db()->query("SELECT id, nome, papel FROM membros_equipe WHERE ativo=1 ORDER BY nome")->fetchAll();
}

$titulo = 'Chamado #' . $id;
$pagina = 'painel';
require __DIR__ . '/includes/header.php';

$cls = ['Aberto'=>'pendente','Em Progresso'=>'pendente','Resolvido'=>'aceito','Encerrado'=>'rejeitado'][$ch['status']] ?? 'pendente';
?>
<p style="margin-bottom:.5rem;"><a href="painel.php">← Voltar aos chamados</a></p>
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
  <h1 style="margin:0;">#<?= (int)$ch['id'] ?> · <?= e($ch['assunto']) ?></h1>
  <span class="badge badge--<?= $cls ?>" style="font-size:.9rem;"><?= e($ch['status']) ?></span>
</div>
<p class="muted">
  <?= e($ch['categoria']) ?> · aberto por <?= e($ch['desportista']) ?> (<?= e($ch['desportista_email']) ?>) ·
  <?= e(date('d/m/Y H:i', strtotime($ch['criado_em']))) ?>
  <?= $ch['atendente_nome'] ? ' · atendente: ' . e($ch['atendente_nome']) : '' ?>
</p>

<?php if ($msg): ?><div class="alerta alerta--ok mt-2"><?= e($msg) ?></div><?php endif; ?>

<div class="grade grade--2 mt-2">
  <div class="card">
    <h3>Mensagem do desportista</h3>
    <p style="white-space:pre-wrap;"><?= e($ch['descricao']) ?></p>

    <?php if ($ch['resposta']): ?>
      <hr style="border:none;border-top:1px solid var(--line);margin:1.2rem 0;">
      <h3>Resposta da equipe</h3>
      <p style="white-space:pre-wrap;"><?= e($ch['resposta']) ?></p>
      <p class="muted" style="font-size:.85rem;">Respondido em <?= e(date('d/m/Y H:i', strtotime($ch['data_resposta']))) ?></p>
    <?php endif; ?>
  </div>

  <div>
    <?php if (in_array($ch['status'], ['Aberto','Em Progresso'], true)): ?>
      <div class="card">
        <h3>Atender</h3>
        <?php if ($ch['status'] === 'Aberto'): ?>
          <form method="post" style="margin-bottom:1rem;">
            <input type="hidden" name="acao" value="aceitar">
            <button class="btn btn--block">Aceitar chamado</button>
          </form>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="acao" value="responder">
          <div class="campo">
            <label for="resposta">Resposta ao desportista</label>
            <textarea id="resposta" name="resposta" required><?= e($ch['resposta'] ?? '') ?></textarea>
          </div>
          <label style="display:flex;gap:.5rem;align-items:center;font-size:.92rem;margin-bottom:1rem;">
            <input type="checkbox" name="resolver" value="1" style="width:auto;">
            Marcar como resolvido ao enviar
          </label>
          <button class="btn btn--block">Enviar resposta</button>
        </form>

        <?php if ($ch['status'] === 'Em Progresso'): ?>
          <form method="post" class="mt-2">
            <input type="hidden" name="acao" value="resolver">
            <button class="btn btn--ghost btn--block">Marcar como resolvido</button>
          </form>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="card">
        <h3>Chamado <?= e($ch['status']) ?></h3>
        <p class="muted">Este chamado não está mais em atendimento.</p>
      </div>
    <?php endif; ?>

    <?php if ($m['papel'] === 'Gestor' && $membros): ?>
      <div class="card">
        <h3>Reatribuir</h3>
        <form method="post" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;">
          <input type="hidden" name="acao" value="reatribuir">
          <div class="campo" style="margin:0;flex:1;min-width:160px;">
            <label for="atendente_id">Atribuir a</label>
            <select id="atendente_id" name="atendente_id">
              <?php foreach ($membros as $mm): ?>
                <option value="<?= (int)$mm['id'] ?>" <?= (int)$ch['atendente_id']===(int)$mm['id']?'selected':'' ?>>
                  <?= e($mm['nome']) ?> (<?= e($mm['papel']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn--ghost">Reatribuir</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
