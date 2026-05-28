<?php
require_once __DIR__ . '/includes/auth.php';
exigir_equipe();
$m = membro_atual();

$erros = []; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova = $_POST['nova'] ?? '';
    $conf = $_POST['conf'] ?? '';

    if (strlen($nova) < 8 || !preg_match('/[A-Za-z]/', $nova) || !preg_match('/[0-9]/', $nova)) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres, com letras e números.';
    }
    if ($nova !== $conf) $erros[] = 'As senhas não conferem.';

    if (!$erros) {
        db()->prepare("UPDATE membros_equipe SET senha_hash=?, primeiro_acesso=0 WHERE id=?")
            ->execute([password_hash($nova, PASSWORD_DEFAULT), $m['id']]);
        header('Location: painel.php');
        exit;
    }
}

$titulo = 'Trocar senha';
require __DIR__ . '/includes/header.php';
?>
<div class="conteudo--estreito" style="margin:0 auto;">
  <h1 class="center">Definir nova senha</h1>
  <?php if ($m['primeiro_acesso']): ?>
    <p class="center muted">Por ser seu primeiro acesso, defina uma senha pessoal.</p>
  <?php endif; ?>

  <div class="card mt-2">
    <?php if ($erros): ?><div class="alerta alerta--erro"><?= implode('<br>', array_map('e', $erros)) ?></div><?php endif; ?>
    <form method="post">
      <div class="campo">
        <label for="nova">Nova senha</label>
        <input type="password" id="nova" name="nova" required>
        <span class="ajuda">Mínimo 8 caracteres, com letras e números.</span>
      </div>
      <div class="campo">
        <label for="conf">Confirmar senha</label>
        <input type="password" id="conf" name="conf" required>
      </div>
      <button class="btn btn--block">Salvar nova senha</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
