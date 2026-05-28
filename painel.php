<?php
require_once __DIR__ . '/includes/auth.php';
exigir_equipe();
$m = membro_atual();
$cats = categorias_do_membro($m);

// Filtros (PBI 009)
$fStatus = trim($_GET['status'] ?? '');
$fIni    = trim($_GET['ini'] ?? '');
$fFim    = trim($_GET['fim'] ?? '');

// Base: atendente só enxerga chamados das suas categorias; gestor vê todos.
$where  = [];
$params = [];

$placeholders = implode(',', array_fill(0, count($cats), '?'));
$where[] = "c.categoria IN ($placeholders)";
$params = array_merge($params, $cats);

if ($fStatus !== '') { $where[] = 'c.status = ?'; $params[] = $fStatus; }
if ($fIni !== '')    { $where[] = 'c.criado_em >= ?'; $params[] = $fIni . ' 00:00:00'; }
if ($fFim !== '')    { $where[] = 'c.criado_em <= ?'; $params[] = $fFim . ' 23:59:59'; }

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT c.*, u.nome AS desportista, me.nome AS atendente_nome
        FROM chamados_suporte c
        JOIN usuarios u ON u.id = c.usuario_id
        LEFT JOIN membros_equipe me ON me.id = c.atendente_id
        $sqlWhere
        ORDER BY FIELD(c.status,'Aberto','Em Progresso','Resolvido','Encerrado'), c.criado_em DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$chamados = $stmt->fetchAll();

// Métricas (respeitando as categorias do membro)
function contar(array $cats, string $status = null): int {
    $ph = implode(',', array_fill(0, count($cats), '?'));
    $sql = "SELECT COUNT(*) c FROM chamados_suporte WHERE categoria IN ($ph)";
    $p = $cats;
    if ($status) { $sql .= ' AND status = ?'; $p[] = $status; }
    $st = db()->prepare($sql); $st->execute($p);
    return (int) $st->fetch()['c'];
}
$mAberto = contar($cats, 'Aberto');
$mProg   = contar($cats, 'Em Progresso');
$mResol  = contar($cats, 'Resolvido');
$mEnc    = contar($cats, 'Encerrado');

$STATUS = ['Aberto', 'Em Progresso', 'Resolvido', 'Encerrado'];
$titulo = 'Chamados';
$pagina = 'painel';
require __DIR__ . '/includes/header.php';

function badge_status(string $s): string {
    $cls = ['Aberto'=>'pendente','Em Progresso'=>'pendente','Resolvido'=>'aceito','Encerrado'=>'rejeitado'][$s] ?? 'pendente';
    return '<span class="badge badge--' . $cls . '">' . e($s) . '</span>';
}
?>
<h1>Central de chamados</h1>
<p class="muted">
  <?= $m['papel']==='Gestor'
      ? 'Como gestor, você vê todos os chamados.'
      : 'Você atende as categorias: ' . e(implode(', ', $cats)) . '.' ?>
</p>

<div class="metricas mt-2">
  <div class="metrica"><div class="metrica__num"><?= $mAberto ?></div><div class="metrica__rot">Abertos</div></div>
  <div class="metrica"><div class="metrica__num"><?= $mProg ?></div><div class="metrica__rot">Em progresso</div></div>
  <div class="metrica"><div class="metrica__num"><?= $mResol ?></div><div class="metrica__rot">Resolvidos</div></div>
  <div class="metrica"><div class="metrica__num"><?= $mEnc ?></div><div class="metrica__rot">Encerrados</div></div>
</div>

<form method="get" class="card mt-2" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
  <div class="campo" style="margin:0;flex:1;min-width:160px;">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="">Todos</option>
      <?php foreach ($STATUS as $s): ?>
        <option value="<?= e($s) ?>" <?= $fStatus===$s?'selected':'' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo" style="margin:0;">
    <label for="ini">De</label>
    <input type="date" id="ini" name="ini" value="<?= e($fIni) ?>">
  </div>
  <div class="campo" style="margin:0;">
    <label for="fim">Até</label>
    <input type="date" id="fim" name="fim" value="<?= e($fFim) ?>">
  </div>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($fStatus||$fIni||$fFim): ?><a class="btn btn--ghost" href="painel.php">Limpar</a><?php endif; ?>
</form>

<div class="section-titulo"><h2><?= count($chamados) ?> chamado(s)</h2></div>

<?php if (!$chamados): ?>
  <div class="card vazio">Nenhum chamado com esses filtros.</div>
<?php else: ?>
  <?php foreach ($chamados as $c): ?>
    <a class="card" href="chamado.php?id=<?= (int)$c['id'] ?>" style="display:block;text-decoration:none;color:inherit;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <strong>#<?= (int)$c['id'] ?> · <?= e($c['assunto']) ?></strong>
        <?= badge_status($c['status']) ?>
      </div>
      <div class="muted" style="font-size:.85rem;margin:.3rem 0;">
        <?= e($c['categoria']) ?> · por <?= e($c['desportista']) ?> ·
        <?= e(date('d/m/Y H:i', strtotime($c['criado_em']))) ?>
        <?= $c['atendente_nome'] ? ' · atendente: ' . e($c['atendente_nome']) : '' ?>
      </div>
      <p class="perfil-card__bio" style="margin:0;"><?= e(mb_strimwidth($c['descricao'], 0, 130, '…')) ?></p>
    </a>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
