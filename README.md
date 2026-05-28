# Eterna Forma

Plataforma web de **matchmaking fitness para o público 40+**. Conecta desportistas a
parceiros de treino compatíveis com base em objetivo, tipo de treino, nível, frequência
e idade, e conta com uma **Equipe Técnica** (Gestor + Atendentes) que dá suporte,
habilita treinos e acompanha a frequência dos desportistas. Projeto acadêmico
(Bacharelado em Engenharia de Software — PUCPR) em **PHP + MySQL + JavaScript**.

## Funcionalidades implementadas

### Desportista
| PBI | Funcionalidade | Onde |
|-----|----------------|------|
| 001 | Cadastro do desportista (validações de senha, e-mail duplicado e idade 40+) | `cadastro.php` |
| 002 | Login com mensagem genérica de erro por segurança | `login.php` |
| 003 | Perfil fitness com cálculo automático de IMC | `perfil.php` |
| 004 | Busca de parceiros com filtros e ordenação por compatibilidade | `buscar.php` |
| 005 | Solicitar / aceitar / recusar / desfazer match | `matches.php`, `api/match.php` |
| 006 | Abrir, acompanhar, encerrar e reabrir chamados de suporte | `suporte.php` |
| —   | Favoritar perfis | `api/favorito.php` |
| —   | Ver treinos habilitados pela equipe | `treinos.php` |

### Equipe Técnica (Gestor e Atendente)
| PBI | Funcionalidade | Onde |
|-----|----------------|------|
| 007 | Cadastro da Equipe Técnica (auto-cadastro como **Gestor**) | `cadastro_equiptec.php` |
| 008 | Login da Equipe Técnica (mesmo login, **tipo detectado automaticamente**) | `login.php` |
| 009 | Painel de chamados com métricas e filtros por status/período | `painel.php` |
| 010 | Aceitar, responder, resolver e reatribuir chamados | `chamado.php` |
| 011 | Gestor cadastra atendentes e define categorias que podem atender | `atendentes.php` |
| 012 | Login do atendente com **troca de senha obrigatória no 1º acesso** | `trocar_senha.php` |
| 013 | Habilitar/desabilitar treinos para um desportista | `desportistas.php` |
| 014 | Verificar dados de frequência e assiduidade do desportista | `desportistas.php` |

O **algoritmo de compatibilidade** (0–100%) está em `includes/auth.php`
(`compatibilidade()`) e pondera objetivo, tipo de treino, nível, frequência e idade.

## Como funciona o login (detecção de tipo)

Existe **um único** `login.php`. Ao entrar, o sistema procura o e-mail primeiro entre os
desportistas e depois entre os membros da equipe; conforme onde encontrar, direciona para
o painel correto (desportista → `dashboard.php`; equipe → `painel.php`). Atendente em
primeiro acesso é levado a `trocar_senha.php`.

Para se cadastrar como Equipe Técnica, acesse **`localhost/eterna-forma/cadastro_equiptec.php`**
— isso cria uma conta de **Gestor**, que depois cadastra os atendentes pelo menu *Atendentes*.

## Como rodar (XAMPP / WAMP / MAMP)

1. **Copie a pasta** `eterna-forma/` para o diretório web (no XAMPP é `htdocs/`).

2. **Crie o banco.** No phpMyAdmin (`http://localhost/phpmyadmin`), aba **SQL**, cole o
   conteúdo de `sql/schema.sql`. Ou via terminal:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   Cria o banco `eterna_forma`, as tabelas e os dados de exemplo.

   > **Já tinha a versão anterior (só desportistas)?** Não precise apagar nada: rode
   > apenas a migração que adiciona a equipe técnica:
   > ```bash
   > mysql -u root -p eterna_forma < sql/equipe_tecnica.sql
   > ```

3. **Ajuste as credenciais** (se necessário) em `config/db.php`.

4. **Acesse** `http://localhost/eterna-forma/`.

## Contas de teste

Todas usam a senha **`senha1234`**:

| Tipo | E-mail |
|------|--------|
| Desportista | `ana@exemplo.com` (também carlos@, marta@, paulo@) |
| Gestor (Equipe Técnica) | `gestor@eternaforma.com` |
| Atendente | `atendente@eternaforma.com` |

Sugestão de roteiro: entre como **Ana**, abra um chamado em *Suporte*; entre como
**gestor** ou **atendente**, responda o chamado em *Chamados*, habilite um treino em
*Desportistas*; volte como **Ana** e veja a resposta e o treino em *Meus treinos*.

## Estrutura

```
eterna-forma/
├── config/db.php              Conexão PDO/MySQL
├── includes/
│   ├── auth.php               Sessão (2 tipos de conta), helpers, compatibilidade
│   ├── header.php             Cabeçalho + navegação por papel
│   └── footer.php
├── assets/css/style.css  assets/js/app.js
├── api/match.php  api/favorito.php
├── sql/
│   ├── schema.sql             Instalação completa + dados de exemplo
│   └── equipe_tecnica.sql     Migração p/ instalações antigas
├── index.php
├── cadastro.php  cadastro_equiptec.php  login.php  logout.php  trocar_senha.php
├── dashboard.php  perfil.php  buscar.php  matches.php  treinos.php  suporte.php
├── painel.php  chamado.php  atendentes.php  desportistas.php
└── README.md
```

## Segurança

- Senhas com `password_hash()` (bcrypt) e `password_verify()`.
- Consultas com **prepared statements** (PDO) contra SQL Injection.
- Saída HTML escapada com `htmlspecialchars()` (helper `e()`) contra XSS.
- Sessão de servidor; rotas protegidas por `exigir_login()`, `exigir_equipe()` e `exigir_gestor()`.
- Atendentes só enxergam chamados das categorias autorizadas pelo gestor.
- `SET NAMES utf8mb4` no SQL garante acentuação correta mesmo em importação via terminal.
