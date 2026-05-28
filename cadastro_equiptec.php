<?php
require_once __DIR__ . '/includes/auth.php';
if (tipo_conta()) { header('Location: ' . home_url()); exit; }

$erros = [];
$dados = ['nome' => '', 'email' => '', 'especialidade' => '', 'telefone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $k => $_) {
        $dados[$k] = trim($_POST[$k] ?? '');
    }
    $senha = $_POST['senha'] ?? '';

    if ($dados['nome'] === '')  $erros[] = 'Informe o nome.';
    if ($dados['email'] === '') $erros[] = 'Informe o e-mail.';
    elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

    // Recomendação de e-mail corporativo (PBI 007 — caso de teste 4: aviso, não bloqueia)
    $aviso = '';
    $dominiosPessoais = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com'];
    $dominio = strtolower(substr(strrchr($dados['email'], '@') ?: '', 1));
    if ($dominio && in_array($dominio, $dominiosPessoais, true)) {
        $aviso = 'Recomendamos usar um e-mail corporativo por segurança, mas você pode continuar.';
    }

    if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres, com letras e números.';
    }

    if (!$erros) {
        // E-mail já usado (em qualquer um dos dois tipos de conta)?
        $existe = false;
        foreach (['usuarios', 'membros_equipe'] as $t) {
            $st = db()->prepare("SELECT 1 FROM $t WHERE email = ?");
            $st->execute([$dados['email']]);
            if ($st->fetch()) { $existe = true; break; }
        }
        if ($existe) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    if (!$erros) {
        // O auto-cadastro cria um GESTOR (que depois cadastra atendentes).
        $st = db()->prepare(
            'INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, telefone, categorias)
             VALUES (?, ?, ?, "Gestor", ?, ?, "Problema técnico,Dúvida,Sugestão")'
        );
        $st->execute([
            $dados['email'],
            password_hash($senha, PASSWORD_DEFAULT),
            $dados['nome'],
            $dados['especialidade'] ?: null,
            $dados['telefone'] ?: null,
        ]);
        $_SESSION['tipo']      = 'equipe';
        $_SESSION['membro_id'] = (int) db()->lastInsertId();
        header('Location: painel.php');
        exit;
    }
}

$titulo = 'Cadastro Equipe Técnica';
require __DIR__ . '/includes/header.php';
?>
<div class="conteudo--estreito" style="margin:0 auto;">
  <h1 class="center">Cadastro · Equipe Técnica</h1>
  <p class="center muted">Você criará uma conta de <strong>Gestor</strong>, podendo depois cadastrar atendentes.</p>

  <div class="card mt-2">
    <?php if ($erros): ?>
      <div class="alerta alerta--erro"><?= implode('<br>', array_map('e', $erros)) ?></div>
    <?php endif; ?>

    <form id="form-cadastro" method="post" novalidate>
      <div class="campo">
        <label for="nome">Nome completo</label>
        <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>" required>
      </div>
      <div class="campo">
        <label for="email">E-mail corporativo</label>
        <input type="email" id="email" name="email" value="<?= e($dados['email']) ?>" required>
        <span class="ajuda">Prefira um e-mail da empresa (ex: nome@suaacademia.com).</span>
      </div>
      <div class="linha-2">
        <div class="campo">
          <label for="especialidade">Especialidade / setor</label>
          <input type="text" id="especialidade" name="especialidade" value="<?= e($dados['especialidade']) ?>" placeholder="ex: Coordenação">
        </div>
        <div class="campo">
          <label for="telefone">Telefone</label>
          <input type="text" id="telefone" name="telefone" value="<?= e($dados['telefone']) ?>" placeholder="(41) 90000-0000">
        </div>
      </div>
      <div class="campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
        <span class="ajuda">Mínimo 8 caracteres, com letras e números.</span>
      </div>
      <button type="submit" class="btn btn--block">Criar conta de gestor</button>
    </form>
  </div>
  <p class="center mt-2 muted">Já tem conta? <a href="login.php">Entrar</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
