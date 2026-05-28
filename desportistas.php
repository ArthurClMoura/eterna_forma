<?php
require_once __DIR__ . '/includes/auth.php';
exigir_equipe();
$m = membro_atual();

$busca = trim($_GET['q'] ?? '');
$id    = (int) ($_GET['id'] ?? 0);
$msg   = '';

// Ações sobre treinos (PBI 013)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $uid  = (int) ($_POST['usuario_id'] ?? 0);

    if ($acao === 'habilitar' && $uid > 0) {
        $treinoId = (int) ($_POST['treino_id'] ?? 0);
        $obs      = trim($_POST['observacoes'] ?? '');
        $semanas  = max(1, (int) ($_POST['semanas'] ?? 4));
        if ($treinoId > 0) {
            db()->prepare(
                "INSERT INTO treinos_habilitados (usuario_id, treino_id, atendente_id, observacoes, data_inicio, data_fim, status)
                 VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? WEEK), 'Ativo')"
            )->execute([$uid, $treinoId, $m['id'], $obs ?: null, $semanas]);
            $msg = 'Treino habilitado para o desportista.';
        }
    } elseif ($acao === 'desabilitar') {
        $thId = (int) ($_POST['th_id'] ?? 0);
        db()->prepare("UPDATE treinos_habilitados SET status='Inativo' WHERE id=?")->execute([$thId]);
        $msg = 'Treino desabilitado.';
    }
    $id = $uid ?: $id; // permanece no perfil
}

// Busca de desportistas (por nome ou id) — PBI 013 caso de teste 1
$lista = [];
if ($busca !== '') {
    $st = db()->prepare(
        "SELECT id, nome, cidade, estado, data_nascimento FROM usuarios
         WHERE ativo=1 AND (nome LIKE ? OR id = ?) ORDER BY nome LIMIT 20"
    );
    $st->execute(['%' . $busca . '%', ctype_digit($busca) ? (int)$busca : 0]);
    $lista = $st->fetchAll();
} elseif ($id === 0) {
    $lista = db()->query("SELECT id, nome, cidade, estado, data_nascimento FROM usuarios WHERE ativo=1 ORDER BY nome LIMIT 20")->fetchAll();
}

// Perfil selecionado
$desp = null; $perfil = null; $habilitados = []; $freq = []; $stats = [];
if ($id > 0) {
    $st = db()->prepare("SELECT * FROM usuarios WHERE id=?"); $st->execute([$id]); $desp = $st->fetch();
    if ($desp) {
        $st = db()->prepare("SELECT * FROM perfis_fitness WHERE usuario_id=?"); $st->execute([$id]); $perfil = $st->fetch() ?: [];

        $st = db()->prepare(
            "SELECT th.*, t.nome AS treino_nome, t.tipo, t.nivel
             FROM treinos_habilitados th JOIN treinos t ON t.id = th.treino_id
             WHERE th.usuario_id=? ORDER BY th.status='Ativo' DESC, th.criado_em DESC"
        );
        $st->execute([$id]); $habilitados = $st->fetchAll();

        // Frequência (PBI 014) — últimas 30/60/90 dias
        $st = db()->prepare(
            "SELECT * FROM frequencia_treinos WHERE usuario_id=? ORDER BY data_treino DESC LIMIT 30"
        );
        $st->execute([$id]); $freq = $st->fetchAll();

        // Estatísticas de assiduidade
        $st = db()->prepare(
            "SELECT
               COUNT(*) total,
               COALESCE(SUM(data_treino >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),0) ult30,
               COALESCE(AVG(duracao_minutos),0) dur_media,
               MAX(data_treino) ultimo
             FROM frequencia_treinos WHERE usuario_id=?"
        );
        $st->execute([$id]); $stats = $st->fetch();
    }
}

$treinosLib = db()->query("SELECT * FROM treinos ORDER BY nivel, nome")->fetchAll();

$titulo = 'Desportistas';
$pagina = 'desportistas';
require __DIR__ . '/includes/header.php';
?>
<h1>Gerenciar desportistas</h1>
<?php if ($msg): ?><div class="alerta alerta--ok"><?= e($msg) ?></div><?php endif; ?>

<form method="get" class="card" style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:flex-end;">
  <div class="campo" style="margin:0;flex:1;min-width:220px;">
    <label for="q">Buscar por nome ou ID</label>
    <input type="text" id="q" name="q" value="<?= e($busca) ?>" placeholder="ex: Ana ou 1">
  </div>
  <button class="btn">Buscar</button>
  <?php if ($busca || $id): ?><a class="btn btn--ghost" href="desportistas.php">Limpar</a><?php endif; ?>
</form>

<?php if (!$desp): ?>
  <div class="section-titulo"><h2><?= count($lista) ?> desportista(s)</h2></div>
  <div class="grade grade--auto">
    <?php foreach ($lista as $d): ?>
      <a class="card perfil-card" href="desportistas.php?id=<?= (int)$d['id'] ?>" style="text-decoration:none;color:inherit;">
        <div class="perfil-card__topo">
          <div class="avatar"><?= e(iniciais($d['nome'])) ?></div>
          <div>
            <div class="perfil-card__nome"><?= e($d['nome']) ?></div>
            <div class="perfil-card__meta">
              #<?= (int)$d['id'] ?> · <?= calcular_idade($d['data_nascimento']) ?> anos<?= $d['cidade'] ? ' · ' . e($d['cidade']) : '' ?>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (!$lista): ?><div class="card vazio">Nenhum desportista encontrado.</div><?php endif; ?>
  </div>

<?php else: /* ---- Perfil do desportista selecionado ---- */ ?>
  <p style="margin:.5rem 0;"><a href="desportistas.php">← Voltar à lista</a></p>

  <div class="card perfil-card">
    <div class="perfil-card__topo">
      <div class="avatar"><?= e(iniciais($desp['nome'])) ?></div>
      <div>
        <div class="perfil-card__nome"><?= e($desp['nome']) ?> <span class="muted" style="font-size:.9rem;">#<?= (int)$desp['id'] ?></span></div>
        <div class="perfil-card__meta">
          <?= calcular_idade($desp['data_nascimento']) ?> anos<?= $desp['cidade'] ? ' · ' . e($desp['cidade']) . '/' . e($desp['estado']) : '' ?> · <?= e($desp['email']) ?>
        </div>
      </div>
    </div>
    <div class="tags">
      <?php if (!empty($perfil['objetivo_principal'])): ?><span class="tag tag--terra"><?= e($perfil['objetivo_principal']) ?></span><?php endif; ?>
      <?php if (!empty($perfil['tipo_treino_principal'])): ?><span class="tag"><?= e($perfil['tipo_treino_principal']) ?></span><?php endif; ?>
      <?php if (!empty($perfil['nivel_experiencia'])): ?><span class="tag"><?= e($perfil['nivel_experiencia']) ?></span><?php endif; ?>
    </div>
  </div>

  <div class="grade grade--2 mt-2">
    <!-- Treinos (PBI 013) -->
    <div>
      <div class="card">
        <h3>Habilitar treino</h3>
        <form method="post">
          <input type="hidden" name="acao" value="habilitar">
          <input type="hidden" name="usuario_id" value="<?= (int)$desp['id'] ?>">
          <div class="campo">
            <label for="treino_id">Treino da biblioteca</label>
            <select id="treino_id" name="treino_id" required>
              <option value="">Selecione</option>
              <?php foreach ($treinosLib as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['nome']) ?> — <?= e($t['nivel']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="campo">
            <label for="semanas">Validade (semanas)</label>
            <input type="number" id="semanas" name="semanas" min="1" max="52" value="4">
          </div>
          <div class="campo">
            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes" placeholder="ex: Aumentar carga progressivamente"></textarea>
          </div>
          <button class="btn btn--block">Habilitar treino</button>
        </form>
      </div>

      <div class="card">
        <h3>Treinos habilitados</h3>
        <?php if (!$habilitados): ?>
          <p class="muted" style="margin:0;">Nenhum treino habilitado ainda.</p>
        <?php else: ?>
          <?php foreach ($habilitados as $h): ?>
            <div style="padding:.7rem 0;border-bottom:1px solid var(--line);">
              <div style="display:flex;justify-content:space-between;gap:.5rem;align-items:center;">
                <strong><?= e($h['treino_nome']) ?></strong>
                <span class="badge badge--<?= $h['status']==='Ativo'?'aceito':'rejeitado' ?>"><?= e($h['status']) ?></span>
              </div>
              <div class="muted" style="font-size:.82rem;">
                <?= e($h['tipo']) ?> · <?= e($h['nivel']) ?>
                <?php if ($h['data_fim']): ?> · até <?= e(date('d/m/Y', strtotime($h['data_fim']))) ?><?php endif; ?>
              </div>
              <?php if ($h['observacoes']): ?><div style="font-size:.88rem;">“<?= e($h['observacoes']) ?>”</div><?php endif; ?>
              <?php if ($h['status']==='Ativo'): ?>
                <form method="post" style="margin-top:.4rem;">
                  <input type="hidden" name="acao" value="desabilitar">
                  <input type="hidden" name="th_id" value="<?= (int)$h['id'] ?>">
                  <input type="hidden" name="usuario_id" value="<?= (int)$desp['id'] ?>">
                  <button class="btn btn--ghost btn--sm">Desabilitar</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Frequência (PBI 014) -->
    <div>
      <div class="card">
        <h3>Frequência</h3>
        <div class="metricas">
          <div class="metrica"><div class="metrica__num"><?= (int)($stats['ult30'] ?? 0) ?></div><div class="metrica__rot">Treinos (30 dias)</div></div>
          <div class="metrica"><div class="metrica__num"><?= (int)($stats['total'] ?? 0) ?></div><div class="metrica__rot">Total registrado</div></div>
          <div class="metrica"><div class="metrica__num"><?= round((float)($stats['dur_media'] ?? 0)) ?>'</div><div class="metrica__rot">Duração média</div></div>
        </div>
        <?php if (!empty($stats['ultimo'])): ?>
          <p class="muted" style="font-size:.85rem;margin-top:.8rem;">
            Último treino: <?= e(date('d/m/Y', strtotime($stats['ultimo']))) ?>
            <?php
              $dias = (int) ((time() - strtotime($stats['ultimo'])) / 86400);
              if ($dias >= 7) echo ' — <strong style="color:var(--terra-deep);">inativo há ' . $dias . ' dias</strong>';
            ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="card">
        <h3>Sessões recentes</h3>
        <?php if (!$freq): ?>
          <p class="muted" style="margin:0;">Sem registros de treino.</p>
        <?php else: ?>
          <?php foreach ($freq as $f): ?>
            <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.55rem 0;border-bottom:1px solid var(--line);font-size:.92rem;">
              <span><?= e(date('d/m/Y', strtotime($f['data_treino']))) ?> · <?= e($f['tipo_atividade'] ?? '—') ?></span>
              <span class="muted"><?= (int)$f['duracao_minutos'] ?> min · <?= (int)$f['calorias_queimadas'] ?> kcal</span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
