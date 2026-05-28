<?php
require_once __DIR__ . '/includes/auth.php';
if (tipo_conta()) { header('Location: ' . home_url()); exit; }

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // 1) Tenta como Desportista
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usr = $stmt->fetch();

    if ($usr && password_verify($senha, $usr['senha_hash'])) {
        if (!$usr['ativo']) {
            $erro = 'Esta conta está inativa.';
        } else {
            $_SESSION['tipo']       = 'desportista';
            $_SESSION['usuario_id'] = (int) $usr['id'];
            db()->prepare('UPDATE usuarios SET last_login = NOW() WHERE id = ?')->execute([$usr['id']]);
            header('Location: dashboard.php');
            exit;
        }
    } else {
        // 2) Tenta como Equipe Técnica / Atendente
        $stmt = db()->prepare('SELECT * FROM membros_equipe WHERE email = ?');
        $stmt->execute([$email]);
        $mem = $stmt->fetch();

        if ($mem && password_verify($senha, $mem['senha_hash'])) {
            if (!$mem['ativo']) {
                $erro = 'Esta conta está inativa.';
            } else {
                $_SESSION['tipo']      = 'equipe';
                $_SESSION['membro_id'] = (int) $mem['id'];
                db()->prepare('UPDATE membros_equipe SET last_login = NOW() WHERE id = ?')->execute([$mem['id']]);
                // Primeiro acesso (atendente recém-criado) força troca de senha
                if ($mem['primeiro_acesso']) {
                    header('Location: trocar_senha.php');
                    exit;
                }
                header('Location: painel.php');
                exit;
            }
        } else {
            // Mensagem genérica por segurança (PBI 002)
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$titulo = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="conteudo--estreito" style="margin:0 auto;">
  <h1 class="center">Entrar</h1>

  <div class="card mt-2">
    <?php if ($erro): ?>
      <div class="alerta alerta--erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
      </div>
      <button type="submit" class="btn btn--block">Entrar</button>
    </form>
  </div>

  <p class="center mt-2 muted">Ainda não tem conta? <a href="cadastro.php">Criar conta de desportista</a></p>
  <p class="center muted" style="margin-top:-.6rem;">É da equipe técnica? <a href="cadastro_equiptec.php">Cadastrar equipe técnica</a></p>

  <div class="card mt-2" style="background:var(--bone-2);">
    <p class="muted" style="margin:0 0 .4rem;font-size:.9rem;">
      <strong>Contas de teste</strong> (senha <code>senha1234</code>):
    </p>
    <p class="muted" style="margin:0;font-size:.88rem;">
      Desportista: <code>ana@exemplo.com</code><br>
      Gestor: <code>gestor@eternaforma.com</code><br>
      Atendente: <code>atendente@eternaforma.com</code>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
