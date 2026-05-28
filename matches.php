<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();
$uid = usuario_logado();

// Solicitações recebidas (pendentes em que sou o usuario2)
$recebidas = db()->prepare(
    "SELECT m.*, u.nome, u.cidade, u.estado, u.data_nascimento
     FROM matches m JOIN usuarios u ON u.id = m.usuario1_id
     WHERE m.usuario2_id = ? AND m.status = 'pendente'
     ORDER BY m.criado_em DESC"
);
$recebidas->execute([$uid]);
$recebidas = $recebidas->fetchAll();

// Solicitações enviadas (pendentes em que sou o usuario1)
$enviadas = db()->prepare(
    "SELECT m.*, u.nome, u.cidade, u.estado, u.data_nascimento
     FROM matches m JOIN usuarios u ON u.id = m.usuario2_id
     WHERE m.usuario1_id = ? AND m.status = 'pendente'
     ORDER BY m.criado_em DESC"
);
$enviadas->execute([$uid]);
$enviadas = $enviadas->fetchAll();

// Matches confirmados (em qualquer direção)
$ativos = db()->prepare(
    "SELECT m.*,
            u.nome, u.cidade, u.estado, u.data_nascimento,
            pf.objetivo_principal, pf.tipo_treino_principal
     FROM matches m
     JOIN usuarios u ON u.id = IF(m.usuario1_id = ?, m.usuario2_id, m.usuario1_id)
     LEFT JOIN perfis_fitness pf ON pf.usuario_id = u.id
     WHERE (m.usuario1_id = ? OR m.usuario2_id = ?) AND m.status = 'aceito'
     ORDER BY m.respondido_em DESC"
);
$ativos->execute([$uid, $uid, $uid]);
$ativos = $ativos->fetchAll();

function ini2(string $n): string {
    $p = preg_split('/\s+/', trim($n));
    $i = strtoupper(mb_substr($p[0],0,1));
    if (count($p)>1) $i .= strtoupper(mb_substr(end($p),0,1));
    return $i;
}

$titulo = 'Meus matches';
$pagina = 'matches';
require __DIR__ . '/includes/header.php';
?>
<h1>Meus matches</h1>

<div class="section-titulo"><h2>Solicitações recebidas <?= $recebidas ? '('.count($recebidas).')' : '' ?></h2></div>
<?php if (!$recebidas): ?>
  <div class="card vazio">Nenhuma solicitação pendente.</div>
<?php else: ?>
  <div class="grade grade--auto">
    <?php foreach ($recebidas as $r): ?>
      <div class="card perfil-card">
        <div class="perfil-card__topo">
          <div class="avatar"><?= e(ini2($r['nome'])) ?></div>
          <div>
            <div class="perfil-card__nome"><?= e($r['nome']) ?></div>
            <div class="perfil-card__meta"><?= calcular_idade($r['data_nascimento']) ?> anos</div>
          </div>
        </div>
        <?php if ($r['mensagem_pessoal']): ?>
          <p class="perfil-card__bio">“<?= e($r['mensagem_pessoal']) ?>”</p>
        <?php endif; ?>
        <div class="acoes-card">
          <button class="btn btn--sm" data-acao="aceitar" data-match="<?= (int)$r['id'] ?>" data-alvo="<?= (int)$r['usuario1_id'] ?>">Aceitar</button>
          <button class="btn btn--ghost btn--sm" data-acao="rejeitar" data-match="<?= (int)$r['id'] ?>" data-alvo="<?= (int)$r['usuario1_id'] ?>">Recusar</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="section-titulo"><h2>Conexões confirmadas <?= $ativos ? '('.count($ativos).')' : '' ?></h2></div>
<?php if (!$ativos): ?>
  <div class="card vazio">
    <h3>Você ainda não tem matches</h3>
    <p>Que tal <a href="buscar.php">buscar parceiros compatíveis</a>?</p>
  </div>
<?php else: ?>
  <div class="grade grade--auto">
    <?php foreach ($ativos as $a): ?>
      <div class="card perfil-card">
        <div class="perfil-card__topo">
          <div class="avatar"><?= e(ini2($a['nome'])) ?></div>
          <div>
            <div class="perfil-card__nome"><?= e($a['nome']) ?></div>
            <div class="perfil-card__meta">
              <?= calcular_idade($a['data_nascimento']) ?> anos<?= $a['cidade'] ? ' · ' . e($a['cidade']) : '' ?>
            </div>
          </div>
        </div>
        <div class="tags">
          <?php if ($a['objetivo_principal']): ?><span class="tag tag--terra"><?= e($a['objetivo_principal']) ?></span><?php endif; ?>
          <?php if ($a['tipo_treino_principal']): ?><span class="tag"><?= e($a['tipo_treino_principal']) ?></span><?php endif; ?>
        </div>
        <div class="acoes-card">
          <span class="badge badge--aceito">Conectados 🎉</span>
          <button class="btn btn--ghost btn--sm" data-acao="desfazer" data-match="<?= (int)$a['id'] ?>" data-alvo="<?= (int)$a['usuario1_id'] ?>">Desfazer</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($enviadas): ?>
  <div class="section-titulo"><h2>Solicitações enviadas</h2></div>
  <div class="grade grade--auto">
    <?php foreach ($enviadas as $en): ?>
      <div class="card perfil-card">
        <div class="perfil-card__topo">
          <div class="avatar"><?= e(ini2($en['nome'])) ?></div>
          <div>
            <div class="perfil-card__nome"><?= e($en['nome']) ?></div>
            <div class="perfil-card__meta"><?= calcular_idade($en['data_nascimento']) ?> anos</div>
          </div>
        </div>
        <div class="acoes-card"><span class="badge badge--pendente">Aguardando resposta</span></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
