-- ============================================================
--  Eterna Forma — Banco de Dados (MySQL)
--  Plataforma de matchmaking fitness para o público 40+
--  Inclui: Desportistas, Equipe Técnica (Gestor) e Atendentes
-- ============================================================
-- Para criar tudo do zero, rode este arquivo inteiro no
-- phpMyAdmin (aba SQL) ou via terminal:
--   mysql -u root -p < sql/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS eterna_forma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eterna_forma;

-- Garante que acentos sejam gravados corretamente ao importar pelo
-- terminal (mysql < schema.sql). Sem isto, "Dúvida" pode virar "DÃºvida".
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Usuários (Desportistas)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(255) NOT NULL UNIQUE,
  senha_hash      VARCHAR(255) NOT NULL,
  nome            VARCHAR(100) NOT NULL,
  data_nascimento DATE         NOT NULL,
  genero          CHAR(1)      NULL,
  cidade          VARCHAR(100) NULL,
  estado          VARCHAR(2)   NULL,
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login      TIMESTAMP    NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Membros da Equipe Técnica (papel: Gestor ou Atendente)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS membros_equipe (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(255) NOT NULL UNIQUE,
  senha_hash      VARCHAR(255) NOT NULL,
  nome            VARCHAR(100) NOT NULL,
  papel           ENUM('Gestor','Atendente') NOT NULL DEFAULT 'Atendente',
  especialidade   VARCHAR(100) NULL,
  telefone        VARCHAR(30)  NULL,
  categorias      VARCHAR(255) NULL,   -- CSV de categorias que o atendente pode atender
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  primeiro_acesso TINYINT(1)   NOT NULL DEFAULT 0,
  criado_por      INT          NULL,
  criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login      TIMESTAMP    NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Perfil fitness (1:1 com usuário)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfis_fitness (
  id                     INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id             INT NOT NULL UNIQUE,
  objetivo_principal     VARCHAR(50) NULL,
  nivel_experiencia      VARCHAR(20) NULL,
  tipo_treino_principal  VARCHAR(50) NULL,
  frequencia_semanal     INT NULL,
  altura_cm              INT NULL,
  peso_kg                DECIMAL(5,2) NULL,
  bio                    TEXT NULL,
  idade_minima_preferida INT NULL,
  idade_maxima_preferida INT NULL,
  genero_preferido       VARCHAR(20) NULL,
  atualizado_em          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_perfil_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Matches entre desportistas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS matches (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  usuario1_id     INT NOT NULL,
  usuario2_id     INT NOT NULL,
  status          ENUM('pendente','aceito','rejeitado','desfeito') NOT NULL DEFAULT 'pendente',
  mensagem_pessoal VARCHAR(255) NULL,
  criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  respondido_em   TIMESTAMP NULL,
  CONSTRAINT fk_match_u1 FOREIGN KEY (usuario1_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_match_u2 FOREIGN KEY (usuario2_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uq_par (usuario1_id, usuario2_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Favoritos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favoritos (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id           INT NOT NULL,
  usuario_favoritado_id INT NOT NULL,
  criado_em            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fav_u  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_uf FOREIGN KEY (usuario_favoritado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uq_fav (usuario_id, usuario_favoritado_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Chamados de suporte (atendidos pela Equipe Técnica)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chamados_suporte (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT NOT NULL,
  categoria     VARCHAR(50) NOT NULL,
  assunto       VARCHAR(255) NOT NULL,
  descricao     TEXT NOT NULL,
  status        ENUM('Aberto','Em Progresso','Resolvido','Encerrado') NOT NULL DEFAULT 'Aberto',
  atendente_id  INT NULL,
  resposta      TEXT NULL,
  data_resposta TIMESTAMP NULL,
  criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_chamado_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_chamado_atendente FOREIGN KEY (atendente_id) REFERENCES membros_equipe(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Biblioteca de treinos (PBI 013)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS treinos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(100) NOT NULL,
  tipo            VARCHAR(50)  NOT NULL,
  nivel           VARCHAR(20)  NOT NULL,
  descricao       TEXT NULL,
  duracao_minutos INT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Treinos habilitados para um desportista (PBI 013)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS treinos_habilitados (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT NOT NULL,
  treino_id     INT NOT NULL,
  atendente_id  INT NULL,
  observacoes   TEXT NULL,
  data_inicio   DATE NULL,
  data_fim      DATE NULL,
  status        ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_th_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)       ON DELETE CASCADE,
  CONSTRAINT fk_th_treino    FOREIGN KEY (treino_id)    REFERENCES treinos(id)        ON DELETE CASCADE,
  CONSTRAINT fk_th_atendente FOREIGN KEY (atendente_id) REFERENCES membros_equipe(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Frequência de treinos do desportista (PBI 014)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS frequencia_treinos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL,
  data_treino       DATE NOT NULL,
  tipo_atividade    VARCHAR(50) NULL,
  duracao_minutos   INT NULL,
  freq_cardio_media INT NULL,
  calorias_queimadas INT NULL,
  criado_em         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_freq_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Dados de exemplo (senha de TODAS as contas: "senha1234")
-- ============================================================
INSERT INTO usuarios (email, senha_hash, nome, data_nascimento, genero, cidade, estado) VALUES
('ana@exemplo.com',    '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Ana Ribeiro',    '1980-04-12', 'F', 'Curitiba', 'PR'),
('carlos@exemplo.com', '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Carlos Souza',   '1975-09-30', 'M', 'Curitiba', 'PR'),
('marta@exemplo.com',  '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Marta Lima',     '1983-01-22', 'F', 'São José dos Pinhais', 'PR'),
('paulo@exemplo.com',  '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Paulo Andrade',  '1968-07-05', 'M', 'Curitiba', 'PR');

INSERT INTO perfis_fitness
  (usuario_id, objetivo_principal, nivel_experiencia, tipo_treino_principal, frequencia_semanal, altura_cm, peso_kg, bio, idade_minima_preferida, idade_maxima_preferida, genero_preferido)
VALUES
  (1, 'Saúde geral',     'Iniciante',     'Funcional',  3, 165, 68.0, 'Voltando à ativa depois dos 40, procuro parceria leve e constante.', 40, 60, 'Qualquer'),
  (2, 'Ganho de massa',  'Intermediário', 'Musculação', 4, 178, 82.5, 'Treino de manhã cedo, gosto de musculação e foco.',                  45, 65, 'Qualquer'),
  (3, 'Perda de peso',   'Iniciante',     'Cardio',     3, 160, 74.0, 'Quero alguém para caminhadas e bike nos fins de semana.',           40, 55, 'Qualquer'),
  (4, 'Resistência',     'Avançado',      'Funcional',  5, 172, 75.0, 'Maratonista amador, busco grupo para treinos longos.',              50, 70, 'Qualquer');

-- Equipe técnica: 1 Gestor + 1 Atendente
INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, telefone, categorias) VALUES
('gestor@eternaforma.com',   '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Roberta Mendes', 'Gestor',    'Coordenação', '(41) 99999-0000', 'Problema técnico,Dúvida,Sugestão');
INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, categorias, criado_por) VALUES
('atendente@eternaforma.com','$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Diego Farias',  'Atendente', 'Suporte técnico', 'Problema técnico,Dúvida', 1);

-- Biblioteca de treinos
INSERT INTO treinos (nome, tipo, nivel, descricao, duracao_minutos) VALUES
('Hipertrofia - Iniciante',   'Musculação', 'Iniciante',     'Treino de adaptação com foco em grandes grupos musculares.', 50),
('Cardio Leve 40+',           'Cardio',     'Iniciante',     'Caminhada e bike de baixo impacto para iniciantes.',         40),
('Funcional Mobilidade',      'Funcional',  'Intermediário', 'Mobilidade articular e fortalecimento do core.',             45),
('Resistência Avançada',      'Funcional',  'Avançado',      'Circuito de resistência para alunos experientes.',           60),
('Yoga Restaurativa',         'Yoga',       'Iniciante',     'Alongamento e respiração para recuperação ativa.',           35);

-- Um chamado de exemplo aberto pela Ana
INSERT INTO chamados_suporte (usuario_id, categoria, assunto, descricao) VALUES
(1, 'Dúvida', 'Como altero meu objetivo?', 'Quero mudar de "Saúde geral" para "Perda de peso", como faço?');

-- Treino habilitado de exemplo para a Ana
INSERT INTO treinos_habilitados (usuario_id, treino_id, atendente_id, observacoes, data_inicio, data_fim, status) VALUES
(1, 2, 2, 'Começar com 30 minutos e aumentar gradualmente.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 WEEK), 'Ativo');

-- Frequência de exemplo para a Ana (últimas semanas)
INSERT INTO frequencia_treinos (usuario_id, data_treino, tipo_atividade, duracao_minutos, freq_cardio_media, calorias_queimadas) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY),  'Caminhada', 35, 110, 220),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY),  'Funcional', 45, 125, 310),
(1, DATE_SUB(CURDATE(), INTERVAL 6 DAY),  'Caminhada', 40, 108, 250),
(1, DATE_SUB(CURDATE(), INTERVAL 9 DAY),  'Bike',      50, 130, 360);

-- ============================================================
-- Fim do schema
-- ============================================================
