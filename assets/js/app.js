/* ============================================================
   Eterna Forma — JavaScript do cliente
   - Validação de formulários de cadastro/login
   - Ações de match e favorito via fetch (AJAX)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  validacaoCadastro();
  acoesMatch();
});

/* ---------- Validação do cadastro (caso de teste PBI 001) ---------- */
function validacaoCadastro() {
  const form = document.querySelector('#form-cadastro');
  if (!form) return;

  form.addEventListener('submit', (ev) => {
    const senha = form.querySelector('[name="senha"]');
    const erros = [];

    // Senha: mínimo 8 caracteres, com letras e números
    const v = senha.value;
    if (v.length < 8 || !/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) {
      erros.push('A senha deve ter no mínimo 8 caracteres, com letras e números.');
    }

    if (erros.length) {
      ev.preventDefault();
      mostrarErroCliente(form, erros.join(' '));
    }
  });
}

function mostrarErroCliente(form, msg) {
  let box = form.querySelector('.alerta--erro.js-cliente');
  if (!box) {
    box = document.createElement('div');
    box.className = 'alerta alerta--erro js-cliente';
    form.prepend(box);
  }
  box.textContent = msg;
  box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/* ---------- Ações de match / favorito ---------- */
function acoesMatch() {
  document.querySelectorAll('[data-acao]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const acao = btn.dataset.acao;          // solicitar | aceitar | rejeitar | desfazer | favoritar
      const alvo = btn.dataset.alvo;           // id do outro usuário
      const matchId = btn.dataset.match || '';
      const endpoint = acao === 'favoritar' ? 'api/favorito.php' : 'api/match.php';

      btn.disabled = true;
      const textoOriginal = btn.textContent;
      btn.textContent = '...';

      try {
        const resp = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ acao, alvo, match_id: matchId }),
        });
        const data = await resp.json();

        if (!resp.ok || !data.ok) {
          throw new Error(data.erro || 'Não foi possível concluir a ação.');
        }

        // Atualiza a interface conforme a ação
        aplicarResultado(btn, acao, data);
      } catch (err) {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        notificar(err.message, 'erro');
      }
    });
  });
}

function aplicarResultado(btn, acao, data) {
  const card = btn.closest('.card, .perfil-card');

  if (acao === 'solicitar') {
    const acoes = btn.closest('.acoes-card');
    if (acoes) acoes.innerHTML = '<span class="badge badge--pendente">Solicitação enviada</span>';
    notificar('Solicitação de match enviada!', 'ok');
  } else if (acao === 'aceitar') {
    if (card) card.querySelector('.acoes-card')?.replaceChildren(
      criarBadge('aceito', 'Match confirmado 🎉')
    );
    notificar('Match confirmado! Vocês agora estão conectados.', 'ok');
  } else if (acao === 'rejeitar') {
    if (card) card.remove();
    notificar('Solicitação recusada.', 'ok');
  } else if (acao === 'desfazer') {
    if (card) card.remove();
    notificar('Match desfeito.', 'ok');
  } else if (acao === 'favoritar') {
    btn.textContent = data.favoritado ? '★ Favoritado' : '☆ Favoritar';
    btn.disabled = false;
  }
}

function criarBadge(tipo, texto) {
  const b = document.createElement('span');
  b.className = 'badge badge--' + tipo;
  b.textContent = texto;
  return b;
}

/* ---------- Notificação simples (toast) ---------- */
function notificar(msg, tipo = 'ok') {
  let host = document.querySelector('#toasts');
  if (!host) {
    host = document.createElement('div');
    host.id = 'toasts';
    host.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:99;display:flex;flex-direction:column;gap:.5rem;align-items:center;';
    document.body.appendChild(host);
  }
  const t = document.createElement('div');
  t.className = 'alerta alerta--' + (tipo === 'erro' ? 'erro' : 'ok');
  t.style.cssText = 'margin:0;box-shadow:0 10px 30px -10px rgba(0,0,0,.3);min-width:240px;';
  t.textContent = msg;
  host.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
