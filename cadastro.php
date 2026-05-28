<?php
require_once __DIR__ . '/includes/auth.php';
if (usuario_logado()) { header('Location: dashboard.php'); exit; }

$erros = [];
$dados = ['nome' => '', 'email' => '', 'data_nascimento' => '', 'genero' => '', 'cidade' => '', 'estado' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $k => $_) {
        $dados[$k] = trim($_POST[$k] ?? '');
    }
    $senha  = $_POST['senha'] ?? '';

    // Validações (Casos de teste do PBI 001)
    if ($dados['nome'] === '')             $erros[] = 'Informe seu nome.';
    if ($dados['email'] === '')            $erros[] = 'Informe seu e-mail.';
    elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($dados['data_nascimento'] === '')  $erros[] = 'Informe sua data de nascimento.';

    // Idade mínima 40 (público-alvo da plataforma)
    if ($dados['data_nascimento'] !== '' && calcular_idade($dados['data_nascimento']) < 40) {
        $erros[] = 'A Eterna Forma é voltada ao público com 40 anos ou mais.';
    }

    // Senha: mínimo 8 caracteres, letras e números
    if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres, com letras e números.';
    }

    // E-mail já cadastrado?
    if (!$erros) {
        $stmt = db()->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado. Tente fazer login.';
        }
    }

    // Cria a conta
    if (!$erros) {
        $stmt = db()->prepare(
            'INSERT INTO usuarios (email, senha_hash, nome, data_nascimento, genero, cidade, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dados['email'],
            password_hash($senha, PASSWORD_DEFAULT),
            $dados['nome'],
            $dados['data_nascimento'],
            $dados['genero'] ?: null,
            $dados['cidade'] ?: null,
            $dados['estado'] ?: null,
        ]);
        $novoId = (int) db()->lastInsertId();
        // Cria perfil fitness vazio para completar depois
        db()->prepare('INSERT INTO perfis_fitness (usuario_id) VALUES (?)')->execute([$novoId]);

        $_SESSION['usuario_id'] = $novoId;
        header('Location: perfil.php?novo=1');
        exit;
    }
}

$titulo = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<div class="conteudo--estreito" style="margin:0 auto;">
  <h1 class="center">Criar conta</h1>
  <p class="center muted mt-0">Leva menos de um minuto.</p>

  <div class="card mt-2">
    <?php if ($erros): ?>
      <div class="alerta alerta--erro">
        <?= implode('<br>', array_map('e', $erros)) ?>
      </div>
    <?php endif; ?>

    <form id="form-cadastro" method="post" novalidate>
      <div class="campo">
        <label for="nome">Nome completo</label>
        <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($dados['email']) ?>" required>
      </div>
      <div class="linha-2">
        <div class="campo">
          <label for="data_nascimento">Data de nascimento</label>
          <input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($dados['data_nascimento']) ?>" required>
        </div>
        <div class="campo">
          <label for="genero">Gênero</label>
          <select id="genero" name="genero">
            <option value="">Prefiro não informar</option>
            <option value="F" <?= $dados['genero']==='F'?'selected':'' ?>>Feminino</option>
            <option value="M" <?= $dados['genero']==='M'?'selected':'' ?>>Masculino</option>
            <option value="O" <?= $dados['genero']==='O'?'selected':'' ?>>Outro</option>
          </select>
        </div>
      </div>
      <div class="linha-2">
        <div class="campo">
          <label for="cidade">Cidade</label>
          <input type="text" id="cidade" name="cidade" value="<?= e($dados['cidade']) ?>">
        </div>
        <div class="campo">
          <label for="estado">UF</label>
          <input type="text" id="estado" name="estado" maxlength="2" value="<?= e($dados['estado']) ?>" placeholder="PR">
        </div>
      </div>
      <div class="campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
        <span class="ajuda">Mínimo 8 caracteres, com letras e números.</span>
      </div>
      <button type="submit" class="btn btn--block">Criar conta</button>
    </form>
  </div>
  <p class="center mt-2 muted">Já tem conta? <a href="login.php">Entrar</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
