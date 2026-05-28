<?php
require_once __DIR__ . '/includes/auth.php';
if (usuario_logado()) { header('Location: dashboard.php'); exit; }
$titulo = 'Encontre seu parceiro de treino';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <span class="selo">Bem-estar · 40+</span>
  <h1>Treinar fica melhor<br>com a companhia certa.</h1>
  <p class="sub">
    A Eterna Forma conecta pessoas com 40 anos ou mais a parceiros de treino
    compatíveis — por objetivo, rotina e nível. Mais motivação, mais constância,
    mais qualidade de vida.
  </p>
  <div class="hero__acoes">
    <a class="btn" href="cadastro.php">Criar minha conta</a>
    <a class="btn btn--ghost" href="login.php">Já tenho conta</a>
  </div>
</section>

<section class="pilares grade grade--auto">
  <div class="card pilar">
    <h3><span class="num">01</span> Treinos compatíveis</h3>
    <p class="muted">Buscamos parceiros conforme seu objetivo, condicionamento, disponibilidade e preferências.</p>
  </div>
  <div class="card pilar">
    <h3><span class="num">02</span> Conexões reais</h3>
    <p class="muted">Um algoritmo de compatibilidade aproxima pessoas com metas e estilos de treino semelhantes.</p>
  </div>
  <div class="card pilar">
    <h3><span class="num">03</span> Pensado para 40+</h3>
    <p class="muted">Acessibilidade, treinos adaptados e foco em saúde e qualidade de vida ao longo do tempo.</p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
