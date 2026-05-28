<?php
require_once __DIR__ . '/includes/auth.php';
exigir_login();

$uid = usuario_logado();
$ok  = false;
$erros = [];

// Carrega perfil atual (cria registro vazio se não existir)
$stmt = db()->prepare('SELECT * FROM perfis_fitness WHERE usuario_id = ?');
$stmt->execute([$uid]);
$perfil = $stmt->fetch();
if (!$perfil) {
    db()->prepare('INSERT INTO perfis_fitness (usuario_id) VALUES (?)')->execute([$uid]);
    $stmt->execute([$uid]);
    $perfil = $stmt->fetch();
}

$OBJETIVOS = ['Saúde geral', 'Perda de peso', 'Ganho de massa', 'Resistência', 'Mobilidade'];
$NIVEIS    = ['Iniciante', 'Intermediário', 'Avançado'];
$TIPOS     = ['Musculação', 'Cardio', 'Funcional', 'Yoga', 'Pilates', 'Caminhada/Corrida'];
$GENEROS_PREF = ['Qualquer', 'Feminino', 'Masculino'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'objetivo_principal'     => trim($_POST['objetivo_principal'] ?? ''),
        'nivel_experiencia'      => trim($_POST['nivel_experiencia'] ?? ''),
        'tipo_treino_principal'  => trim($_POST['tipo_treino_principal'] ?? ''),
        'frequencia_semanal'     => (int) ($_POST['frequencia_semanal'] ?? 0),
        'altura_cm'              => (int) ($_POST['altura_cm'] ?? 0),
        'peso_kg'                => (float) str_replace(',', '.', $_POST['peso_kg'] ?? '0'),
        'bio'                    => trim($_POST['bio'] ?? ''),
        'idade_minima_preferida' => (int) ($_POST['idade_minima_preferida'] ?? 40),
        'idade_maxima_preferida' => (int) ($_POST['idade_maxima_preferida'] ?? 70),
        'genero_preferido'       => trim($_POST['genero_preferido'] ?? 'Qualquer'),
    ];

    // Validações (caso de teste 3 do PBI 003: valores inválidos)
    if ($campos['altura_cm'] !== 0 && ($campos['altura_cm'] < 100 || $campos['altura_cm'] > 250)) {
        $erros[] = 'Altura deve estar entre 100 e 250 cm.';
    }
    if ($campos['peso_kg'] !== 0.0 && ($campos['peso_kg'] <= 0 || $campos['peso_kg'] > 400)) {
        $erros[] = 'Peso deve ser maior que zero e realista.';
    }
    if ($campos['frequencia_semanal'] < 0 || $campos['frequencia_semanal'] > 7) {
        $erros[] = 'Frequência semanal deve ser de 0 a 7 dias.';
    }

    if (!$erros) {
        $sql = 'UPDATE perfis_fitness SET
                  objetivo_principal = ?, nivel_experiencia = ?, tipo_treino_principal = ?,
                  frequencia_semanal = ?, altura_cm = ?, peso_kg = ?, bio = ?,
                  idade_minima_preferida = ?, idade_maxima_preferida = ?, genero_preferido = ?
                WHERE usuario_id = ?';
        db()->prepare($sql)->execute([
            $campos['objetivo_principal'] ?: null,
            $campos['nivel_experiencia'] ?: null,
            $campos['tipo_treino_principal'] ?: null,
            $campos['frequencia_semanal'] ?: null,
            $campos['altura_cm'] ?: null,
            $campos['peso_kg'] ?: null,
            $campos['bio'] ?: null,
            $campos['idade_minima_preferida'],
            $campos['idade_maxima_preferida'],
            $campos['genero_preferido'],
            $uid,
        ]);
        $ok = true;
        $stmt->execute([$uid]);
        $perfil = $stmt->fetch();
    }
}

// Calcula IMC, se possível (caso de teste 1 do PBI 003)
$imc = null;
if (!empty($perfil['altura_cm']) && !empty($perfil['peso_kg'])) {
    $m = $perfil['altura_cm'] / 100;
    $imc = round($perfil['peso_kg'] / ($m * $m), 1);
}

$titulo = 'Meu perfil';
$pagina = 'perfil';
require __DIR__ . '/includes/header.php';
?>
<h1>Meu perfil fitness</h1>
<?php if (isset($_GET['novo'])): ?>
  <div class="alerta alerta--ok">Conta criada! Complete seu perfil fitness para encontrar parceiros compatíveis.</div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="alerta alerta--ok">Perfil atualizado com sucesso.</div>
<?php endif; ?>
<?php if ($erros): ?>
  <div class="alerta alerta--erro"><?= implode('<br>', array_map('e', $erros)) ?></div>
<?php endif; ?>

<div class="grade grade--2">
  <div class="card">
    <form method="post">
      <div class="linha-2">
        <div class="campo">
          <label for="objetivo_principal">Objetivo principal</label>
          <select id="objetivo_principal" name="objetivo_principal">
            <option value="">Selecione</option>
            <?php foreach ($OBJETIVOS as $o): ?>
              <option value="<?= e($o) ?>" <?= $perfil['objetivo_principal']===$o?'selected':'' ?>><?= e($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="nivel_experiencia">Nível</label>
          <select id="nivel_experiencia" name="nivel_experiencia">
            <option value="">Selecione</option>
            <?php foreach ($NIVEIS as $n): ?>
              <option value="<?= e($n) ?>" <?= $perfil['nivel_experiencia']===$n?'selected':'' ?>><?= e($n) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="linha-2">
        <div class="campo">
          <label for="tipo_treino_principal">Tipo de treino preferido</label>
          <select id="tipo_treino_principal" name="tipo_treino_principal">
            <option value="">Selecione</option>
            <?php foreach ($TIPOS as $t): ?>
              <option value="<?= e($t) ?>" <?= $perfil['tipo_treino_principal']===$t?'selected':'' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="frequencia_semanal">Frequência (dias/semana)</label>
          <input type="number" id="frequencia_semanal" name="frequencia_semanal" min="0" max="7"
                 value="<?= e($perfil['frequencia_semanal']) ?>">
        </div>
      </div>
      <div class="linha-2">
        <div class="campo">
          <label for="altura_cm">Altura (cm)</label>
          <input type="number" id="altura_cm" name="altura_cm" min="100" max="250"
                 value="<?= e($perfil['altura_cm']) ?>">
        </div>
        <div class="campo">
          <label for="peso_kg">Peso (kg)</label>
          <input type="text" id="peso_kg" name="peso_kg" value="<?= e($perfil['peso_kg']) ?>" placeholder="ex: 72.5">
        </div>
      </div>
      <div class="campo">
        <label for="bio">Sobre você</label>
        <textarea id="bio" name="bio" placeholder="Conte um pouco sobre sua rotina e o que procura num parceiro de treino."><?= e($perfil['bio']) ?></textarea>
      </div>

      <h3 style="margin-top:1.4rem;">Preferências de parceiro</h3>
      <div class="linha-2">
        <div class="campo">
          <label for="idade_minima_preferida">Idade mínima</label>
          <input type="number" id="idade_minima_preferida" name="idade_minima_preferida" min="40" max="100"
                 value="<?= e($perfil['idade_minima_preferida'] ?: 40) ?>">
        </div>
        <div class="campo">
          <label for="idade_maxima_preferida">Idade máxima</label>
          <input type="number" id="idade_maxima_preferida" name="idade_maxima_preferida" min="40" max="100"
                 value="<?= e($perfil['idade_maxima_preferida'] ?: 70) ?>">
        </div>
      </div>
      <div class="campo">
        <label for="genero_preferido">Gênero do parceiro</label>
        <select id="genero_preferido" name="genero_preferido">
          <?php foreach ($GENEROS_PREF as $g): ?>
            <option value="<?= e($g) ?>" <?= ($perfil['genero_preferido']?:'Qualquer')===$g?'selected':'' ?>><?= e($g) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn--block">Salvar perfil</button>
    </form>
  </div>

  <div>
    <div class="card">
      <h3>Resumo</h3>
      <?php if ($imc !== null): ?>
        <div class="metrica" style="margin-bottom:1rem;">
          <div class="metrica__num"><?= e((string)$imc) ?></div>
          <div class="metrica__rot">IMC calculado automaticamente</div>
        </div>
      <?php endif; ?>
      <div class="tags">
        <?php if ($perfil['objetivo_principal']): ?><span class="tag tag--terra"><?= e($perfil['objetivo_principal']) ?></span><?php endif; ?>
        <?php if ($perfil['tipo_treino_principal']): ?><span class="tag"><?= e($perfil['tipo_treino_principal']) ?></span><?php endif; ?>
        <?php if ($perfil['nivel_experiencia']): ?><span class="tag"><?= e($perfil['nivel_experiencia']) ?></span><?php endif; ?>
        <?php if ($perfil['frequencia_semanal']): ?><span class="tag"><?= e($perfil['frequencia_semanal']) ?>x / semana</span><?php endif; ?>
      </div>
      <p class="muted mt-2" style="font-size:.9rem;">
        Quanto mais completo o perfil, mais preciso fica o algoritmo de compatibilidade.
      </p>
      <a class="btn btn--ghost btn--block mt-2" href="buscar.php">Buscar parceiros agora</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
