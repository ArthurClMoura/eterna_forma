<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();

$uid = usuario_logado();

// Perfil do usuário logado (para calcular compatibilidade)
$stmt = db()->prepare('SELECT pf.*, u.data_nascimento FROM perfis_fitness pf JOIN usuarios u ON u.id = pf.usuario_id WHERE pf.usuario_id = ?');
$stmt->execute([$uid]);
$meu = $stmt->fetch() ?: [];
$meu['idade'] = !empty($meu['data_nascimento']) ? calcular_idade($meu['data_nascimento']) : null;

// Filtros (PBI 004 — objetivo, faixa etária, tipo de treino)
$fObjetivo = trim($_GET['objetivo'] ?? '');
$fTipo     = trim($_GET['tipo'] ?? '');
$fFaixa    = trim($_GET['faixa'] ?? '');   // ex: 40-50

$sql = 'SELECT u.id, u.nome, u.data_nascimento, u.cidade, u.estado, u.genero,
               pf.objetivo_principal, pf.nivel_experiencia, pf.tipo_treino_principal,
               pf.frequencia_semanal, pf.bio
        FROM usuarios u
        JOIN perfis_fitness pf ON pf.usuario_id = u.id
        WHERE u.id <> ? AND u.ativo = 1';
$params = [$uid];

if ($fObjetivo !== '') { $sql .= ' AND pf.objetivo_principal = ?'; $params[] = $fObjetivo; }
if ($fTipo !== '')     { $sql .= ' AND pf.tipo_treino_principal = ?'; $params[] = $fTipo; }

$stmt = db()->prepare($sql);
$stmt->execute($params);
$candidatos = $stmt->fetchAll();

// Status de match existente entre o usuário e cada candidato
$stmtM = db()->prepare(
    'SELECT * FROM matches
     WHERE (usuario1_id = ? AND usuario2_id = ?) OR (usuario1_id = ? AND usuario2_id = ?)
     LIMIT 1'
);

// Monta lista com idade, compatibilidade e filtro de faixa etária em PHP
$lista = [];
foreach ($candidatos as $c) {
    $idade = calcular_idade($c['data_nascimento']);

    if ($fFaixa !== '') {
        [$min, $max] = array_map('intval', explode('-', $fFaixa));
        if ($idade < $min || $idade > $max) continue;
    }

    $c['idade'] = $idade;
    $c['compat'] = compatibilidade($meu, $c);

    $stmtM->execute([$uid, $c['id'], $c['id'], $uid]);
    $m = $stmtM->fetch();
    $c['match'] = $m ?: null;

    $lista[] = $c;
}

// Ordena por compatibilidade (caso de teste 1 do PBI 004)
usort($lista, fn($a, $b) => $b['compat'] <=> $a['compat']);

$OBJETIVOS = ['Saúde geral', 'Perda de peso', 'Ganho de massa', 'Resistência', 'Mobilidade'];
$TIPOS     = ['Musculação', 'Cardio', 'Funcional', 'Yoga', 'Pilates', 'Caminhada/Corrida'];
$FAIXAS    = ['40-50', '50-60', '60-70', '70-100'];

$titulo = 'Buscar parceiros';
$pagina = 'buscar';
require __DIR__ . '/includes/header.php';
?>
<h1>Buscar parceiros de treino</h1>

<form method="get" class="card" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
  <div class="campo" style="margin:0;flex:1;min-width:170px;">
    <label for="objetivo">Objetivo</label>
    <select id="objetivo" name="objetivo">
      <option value="">Todos</option>
      <?php foreach ($OBJETIVOS as $o): ?>
        <option value="<?= e($o) ?>" <?= $fObjetivo===$o?'selected':'' ?>><?= e($o) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo" style="margin:0;flex:1;min-width:170px;">
    <label for="tipo">Tipo de treino</label>
    <select id="tipo" name="tipo">
      <option value="">Todos</option>
      <?php foreach ($TIPOS as $t): ?>
        <option value="<?= e($t) ?>" <?= $fTipo===$t?'selected':'' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo" style="margin:0;flex:1;min-width:150px;">
    <label for="faixa">Faixa etária</label>
    <select id="faixa" name="faixa">
      <option value="">Todas</option>
      <?php foreach ($FAIXAS as $f): ?>
        <option value="<?= e($f) ?>" <?= $fFaixa===$f?'selected':'' ?>><?= str_replace('-100','+', e($f)) ?> anos</option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn">Filtrar</button>
  <?php if ($fObjetivo||$fTipo||$fFaixa): ?>
    <a class="btn btn--ghost" href="buscar.php">Limpar</a>
  <?php endif; ?>
</form>

<div class="section-titulo">
  <h2><?= count($lista) ?> parceiro<?= count($lista)===1?'':'s' ?> encontrado<?= count($lista)===1?'':'s' ?></h2>
</div>

<?php if (!$lista): ?>
  <div class="card vazio">
    <h3>Nenhum parceiro com esses filtros</h3>
    <p>Tente ampliar os critérios de busca.</p>
  </div>
<?php else: ?>
  <div class="grade grade--auto">
    <?php foreach ($lista as $c): ?>
      <div class="card perfil-card">
        <div class="perfil-card__topo">
          <div class="avatar"><?= e(iniciais($c['nome'])) ?></div>
          <div>
            <div class="perfil-card__nome"><?= e($c['nome']) ?></div>
            <div class="perfil-card__meta">
              <?= (int)$c['idade'] ?> anos<?= $c['cidade'] ? ' · ' . e($c['cidade']) . '/' . e($c['estado']) : '' ?>
            </div>
          </div>
        </div>

        <div class="compat">
          <span>Compatibilidade</span>
          <span class="compat__barra"><span class="compat__fill" style="width:<?= (int)$c['compat'] ?>%"></span></span>
          <span class="compat__num"><?= (int)$c['compat'] ?>%</span>
        </div>

        <div class="tags">
          <?php if ($c['objetivo_principal']): ?><span class="tag tag--terra"><?= e($c['objetivo_principal']) ?></span><?php endif; ?>
          <?php if ($c['tipo_treino_principal']): ?><span class="tag"><?= e($c['tipo_treino_principal']) ?></span><?php endif; ?>
          <?php if ($c['nivel_experiencia']): ?><span class="tag"><?= e($c['nivel_experiencia']) ?></span><?php endif; ?>
        </div>

        <?php if ($c['bio']): ?>
          <p class="perfil-card__bio"><?= e(mb_strimwidth($c['bio'], 0, 120, '…')) ?></p>
        <?php endif; ?>

        <div class="acoes-card">
          <?php
            $m = $c['match'];
            if ($m && $m['status'] === 'aceito'):
          ?>
            <span class="badge badge--aceito">Match confirmado</span>
          <?php elseif ($m && $m['status'] === 'pendente' && (int)$m['usuario1_id'] === $uid): ?>
            <span class="badge badge--pendente">Solicitação enviada</span>
          <?php elseif ($m && $m['status'] === 'pendente' && (int)$m['usuario2_id'] === $uid): ?>
            <a class="btn btn--sm" href="matches.php">Responder solicitação</a>
          <?php else: ?>
            <button class="btn btn--sm" data-acao="solicitar" data-alvo="<?= (int)$c['id'] ?>">Solicitar match</button>
            <button class="btn btn--ghost btn--sm" data-acao="favoritar" data-alvo="<?= (int)$c['id'] ?>">☆ Favoritar</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
