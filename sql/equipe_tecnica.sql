-- ============================================================
--  Eterna Forma — Migração: Equipe Técnica + Atendente
-- ============================================================
-- Use este arquivo SE você JÁ tinha instalado a primeira versão
-- (só com desportistas) e quer apenas adicionar a equipe técnica
-- SEM apagar seus dados. Rode no phpMyAdmin (aba SQL) ou:
--   mysql -u root -p eterna_forma < sql/equipe_tecnica.sql
--
-- Se for instalar do zero, use sql/schema.sql (já inclui tudo).
-- ============================================================

USE eterna_forma;
SET NAMES utf8mb4;

-- 1) Membros da equipe técnica
CREATE TABLE IF NOT EXISTS membros_equipe (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(255) NOT NULL UNIQUE,
  senha_hash      VARCHAR(255) NOT NULL,
  nome            VARCHAR(100) NOT NULL,
  papel           ENUM('Gestor','Atendente') NOT NULL DEFAULT 'Atendente',
  especialidade   VARCHAR(100) NULL,
  telefone        VARCHAR(30)  NULL,
  categorias      VARCHAR(255) NULL,
  ativo           TINYINT(1)   NOT NULL DEFAULT 1,
  primeiro_acesso TINYINT(1)   NOT NULL DEFAULT 0,
  criado_por      INT          NULL,
  criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login      TIMESTAMP    NULL
) ENGINE=InnoDB;

-- 2) Novas colunas em chamados_suporte (ignore o erro se já existirem)
ALTER TABLE chamados_suporte
  ADD COLUMN atendente_id  INT NULL AFTER status,
  ADD COLUMN resposta      TEXT NULL AFTER atendente_id,
  ADD COLUMN data_resposta TIMESTAMP NULL AFTER resposta,
  ADD COLUMN atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD CONSTRAINT fk_chamado_atendente FOREIGN KEY (atendente_id) REFERENCES membros_equipe(id) ON DELETE SET NULL;

-- 3) Biblioteca de treinos
CREATE TABLE IF NOT EXISTS treinos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(100) NOT NULL,
  tipo            VARCHAR(50)  NOT NULL,
  nivel           VARCHAR(20)  NOT NULL,
  descricao       TEXT NULL,
  duracao_minutos INT NULL
) ENGINE=InnoDB;

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

-- 4) Dados de exemplo (senha: "senha1234")
INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, telefone, categorias) VALUES
('gestor@eternaforma.com',   '$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Roberta Mendes', 'Gestor',    'Coordenação', '(41) 99999-0000', 'Problema técnico,Dúvida,Sugestão');
INSERT INTO membros_equipe (email, senha_hash, nome, papel, especialidade, categorias, criado_por) VALUES
('atendente@eternaforma.com','$2b$10$5UwYfMz6SaSc/3MY3PhtGemNyzkn1CNnotFkrG6u5vZpVtYmErpw2', 'Diego Farias',  'Atendente', 'Suporte técnico', 'Problema técnico,Dúvida', 1);

INSERT INTO treinos (nome, tipo, nivel, descricao, duracao_minutos) VALUES
('Hipertrofia - Iniciante', 'Musculação', 'Iniciante',     'Treino de adaptação com foco em grandes grupos musculares.', 50),
('Cardio Leve 40+',         'Cardio',     'Iniciante',     'Caminhada e bike de baixo impacto para iniciantes.',         40),
('Funcional Mobilidade',    'Funcional',  'Intermediário', 'Mobilidade articular e fortalecimento do core.',             45),
('Resistência Avançada',    'Funcional',  'Avançado',      'Circuito de resistência para alunos experientes.',           60),
('Yoga Restaurativa',       'Yoga',       'Iniciante',     'Alongamento e respiração para recuperação ativa.',           35);
