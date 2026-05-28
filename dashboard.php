<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();
$uid = usuario_logado();
$u = usuario_atual();

// Métricas
$stmt = db()->prepare("SELECT COUNT(*) c FROM matches WHERE (usuario1_id=? OR usuario2_id=?) AND status='aceito'");
$stmt->execute([$uid, $uid]); $totalMatches = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c FROM matches WHERE usuario2_id=? AND status='pendente'");
$stmt->execute([$uid]); $pendentes = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c FROM favoritos WHERE usuario_id=?");
$stmt->execute([$uid]); $favs = (int)$stmt->fetch()['c'];

// Perfil completo?
$stmt = db()->prepare('SELECT objetivo_principal, tipo_treino_principal, nivel_experiencia FROM perfis_fitness WHERE usuario_id=?');
$stmt->execute([$uid]); $pf = $stmt->fetch() ?: [];
$perfilCompleto = !empty($pf['objetivo_principal']) && !empty($pf['tipo_treino_principal']) && !empty($pf['nivel_experiencia']);

$titulo = 'Início';
$pagina = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<h1>Olá, <?= e(explode(' ', $u['nome'])[0]) ?> 👋</h1>
<p class="muted">Bem-vindo de volta à Eterna Forma.</p>

<?php if (!$perfilCompleto): ?>
  <div class="alerta alerta--ok" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
    <span>Complete seu perfil fitness para melhorar suas recomendações de parceiros.</span>
    <a class="btn btn--sm" href="perfil.php">Completar perfil</a>
  </div>
<?php endif; ?>

<div class="metricas mt-2">
  <div class="metrica"><div class="metrica__num"><?= $totalMatches ?></div><div class="metrica__rot">Matches confirmados</div></div>
  <div class="metrica"><div class="metrica__num"><?= $pendentes ?></div><div class="metrica__rot">Solicitações pendentes</div></div>
  <div class="metrica"><div class="metrica__num"><?= $favs ?></div><div class="metrica__rot">Favoritos</div></div>
</div>

<div class="grade grade--2 mt-2">
  <div class="card">
    <h3>Encontrar parceiros</h3>
    <p class="muted">Use os filtros de objetivo, idade e tipo de treino para achar pessoas compatíveis com você.</p>
    <a class="btn" href="buscar.php">Buscar agora</a>
  </div>
  <div class="card">
    <h3>Suas solicitações</h3>
    <p class="muted">
      <?= $pendentes > 0
        ? "Você tem $pendentes solicitação(ões) aguardando resposta."
        : 'Nenhuma solicitação pendente no momento.' ?>
    </p>
    <a class="btn btn--ghost" href="matches.php">Ver meus matches</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
