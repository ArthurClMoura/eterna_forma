<?php
require_once __DIR__ . '/includes/auth.php';
exigir_equipe();
exigir_gestor();
$m = membro_atual();

$msg = ''; $erros = []; $criado = null;
$CATEGORIAS = ['Problema técnico', 'Dúvida', 'Sugestão'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'criar';

    if ($acao === 'toggle') {
        $aid = (int) ($_POST['atendente_id'] ?? 0);
        db()->prepare("UPDATE membros_equipe SET ativo = 1 - ativo WHERE id=? AND papel='Atendente'")->execute([$aid]);
        $msg = 'Situação do atendente atualizada.';

    } else { // criar
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $espec = trim($_POST['especialidade'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $cats  = $_POST['categorias'] ?? [];
        $cats  = array_values(array_intersect($CATEGORIAS, is_array($cats) ? $cats : []));

        if ($nome === '')  $erros[] = 'Informe o nome.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
        if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
            $erros[] = 'A senha temporária deve ter no mínimo 8 caracteres, com letras e números.';
        }
        if (!$cats) $erros[] = 'Selecione ao menos uma categoria de atendimento.';

        if (!$erros) {
            foreach (['usuarios', 'membros_equipe'] as $t) {
                $st = db()->prepare("SELECT 1 FROM $t WHERE email = ?");
                $st->execute([$email]);
                if ($st->fetch()) { $erros[] = 'Este e-mail já está cadastrado.'; break; }
            }
        }

        if (!$erros) {
            db()->prepare(
                "INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, categorias, primeiro_acesso, criado_por)
                 VALUES (?, ?, ?, 'Atendente', ?, ?, 1, ?)"
            )->execute([
                $email, password_hash($senha, PASSWORD_DEFAULT), $nome,
                $espec ?: null, implode(',', $cats), $m['id'],
            ]);
            $criado = ['email' => $email, 'senha' => $senha];
            $msg = 'Atendente cadastrado! Repasse as credenciais temporárias abaixo.';
        }
    }
}

$atendentes = db()->query(
    "SELECT * FROM membros_equipe WHERE papel='Atendente' ORDER BY ativo DESC, nome"
)->fetchAll();

$titulo = 'Atendentes';
$pagina = 'atendentes';
require __DIR__ . '/includes/header.php';
?>
<h1>Atendentes</h1>
<p class="muted">Cadastre atendentes e defina quais categorias de chamado cada um pode atender.</p>

<?php if ($msg): ?><div class="alerta alerta--ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($erros): ?><div class="alerta alerta--erro"><?= implode('<br>', array_map('e', $erros)) ?></div><?php endif; ?>
<?php if ($criado): ?>
  <div class="card" style="background:var(--bone-2);">
    <strong>Credenciais temporárias</strong>
    <p class="muted" style="margin:.4rem 0 0;">
      E-mail: <code><?= e($criado['email']) ?></code> · Senha: <code><?= e($criado['senha']) ?></code><br>
      No primeiro acesso, o atendente será obrigado a trocar a senha.
    </p>
  </div>
<?php endif; ?>

<div class="grade grade--2 mt-2">
  <div class="card">
    <h3>Adicionar atendente</h3>
    <form method="post">
      <input type="hidden" name="acao" value="criar">
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required>
      </div>
      <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="campo">
        <label for="especialidade">Especialidade</label>
        <input type="text" id="especialidade" name="especialidade" placeholder="ex: Suporte técnico">
      </div>
      <div class="campo">
        <label>Categorias que pode atender</label>
        <div style="display:flex;flex-direction:column;gap:.4rem;">
          <?php foreach ($CATEGORIAS as $c): ?>
            <label style="display:flex;gap:.5rem;align-items:center;font-weight:400;">
              <input type="checkbox" name="categorias[]" value="<?= e($c) ?>" style="width:auto;"> <?= e($c) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="campo">
        <label for="senha">Senha temporária</label>
        <input type="text" id="senha" name="senha" required placeholder="mín. 8 caracteres com letras e números">
      </div>
      <button class="btn btn--block">Cadastrar atendente</button>
    </form>
  </div>

  <div>
    <h3>Equipe de atendimento</h3>
    <?php if (!$atendentes): ?>
      <div class="card vazio">Nenhum atendente cadastrado.</div>
    <?php else: ?>
      <?php foreach ($atendentes as $a): ?>
        <div class="card" style="padding:1.1rem 1.2rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
            <strong><?= e($a['nome']) ?></strong>
            <span class="badge badge--<?= $a['ativo'] ? 'aceito' : 'rejeitado' ?>"><?= $a['ativo'] ? 'Ativo' : 'Inativo' ?></span>
          </div>
          <div class="muted" style="font-size:.85rem;margin:.25rem 0 .5rem;">
            <?= e($a['email']) ?><?= $a['especialidade'] ? ' · ' . e($a['especialidade']) : '' ?>
          </div>
          <div class="tags">
            <?php foreach (categorias_do_membro($a) as $c): ?><span class="tag"><?= e($c) ?></span><?php endforeach; ?>
          </div>
          <form method="post" class="mt-2">
            <input type="hidden" name="acao" value="toggle">
            <input type="hidden" name="atendente_id" value="<?= (int)$a['id'] ?>">
            <button class="btn btn--ghost btn--sm"><?= $a['ativo'] ? 'Desativar' : 'Reativar' ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
