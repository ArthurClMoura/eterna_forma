<?php
require_once __DIR__ . '/auth.php';
$u = usuario_atual();          // desportista (ou null)
$m = membro_atual();           // membro da equipe (ou null)
$logado = $u ?: $m;
$nome = $logado['nome'] ?? '';
$pagina = $pagina ?? '';
$home = $u ? 'dashboard.php' : ($m ? 'painel.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($titulo) ? e($titulo) . ' · ' : '' ?>Eterna Forma</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topo">
  <div class="topo__inner">
    <a class="marca" href="<?= $home ?>">
      <span class="marca__sigla">EF</span>
      <span class="marca__nome">Eterna&nbsp;Forma</span>
      <?php if ($m): ?><span class="marca__tag"><?= e($m['papel']) ?></span><?php endif; ?>
    </a>

    <?php if ($u): /* ---- Navegação do Desportista ---- */ ?>
      <nav class="nav">
        <a href="dashboard.php" class="<?= $pagina==='dashboard'?'is-ativo':'' ?>">Início</a>
        <a href="buscar.php"    class="<?= $pagina==='buscar'?'is-ativo':'' ?>">Buscar parceiros</a>
        <a href="matches.php"   class="<?= $pagina==='matches'?'is-ativo':'' ?>">Meus matches</a>
        <a href="treinos.php"   class="<?= $pagina==='treinos'?'is-ativo':'' ?>">Meus treinos</a>
        <a href="perfil.php"    class="<?= $pagina==='perfil'?'is-ativo':'' ?>">Meu perfil</a>
        <a href="suporte.php"   class="<?= $pagina==='suporte'?'is-ativo':'' ?>">Suporte</a>
      </nav>
      <div class="nav__user">
        <span class="nav__ola">Olá, <?= e(explode(' ', $nome)[0]) ?></span>
        <a class="btn btn--ghost btn--sm" href="logout.php">Sair</a>
      </div>

    <?php elseif ($m): /* ---- Navegação da Equipe Técnica ---- */ ?>
      <nav class="nav">
        <a href="painel.php"     class="<?= $pagina==='painel'?'is-ativo':'' ?>">Chamados</a>
        <a href="desportistas.php" class="<?= $pagina==='desportistas'?'is-ativo':'' ?>">Desportistas</a>
        <?php if ($m['papel']==='Gestor'): ?>
          <a href="atendentes.php" class="<?= $pagina==='atendentes'?'is-ativo':'' ?>">Atendentes</a>
        <?php endif; ?>
      </nav>
      <div class="nav__user">
        <span class="nav__ola"><?= e($nome) ?></span>
        <a class="btn btn--ghost btn--sm" href="logout.php">Sair</a>
      </div>

    <?php else: /* ---- Visitante ---- */ ?>
      <nav class="nav">
        <a href="login.php" class="btn btn--ghost btn--sm">Entrar</a>
        <a href="cadastro.php" class="btn btn--sm">Criar conta</a>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main class="conteudo">
