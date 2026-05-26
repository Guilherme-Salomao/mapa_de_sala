SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  status ENUM('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  nivel_acesso ENUM('Admin','Gestor','Professor','Apoio') NOT NULL DEFAULT 'Professor',
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  ultimo_login DATETIME DEFAULT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario_areas (
  usuario_id INT NOT NULL,
  area_id INT NOT NULL,
  PRIMARY KEY (usuario_id, area_id),
  KEY fk_usuario_areas_area (area_id),
  CONSTRAINT fk_usuario_areas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_usuario_areas_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  capacidade INT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'ativa',
  descricao TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  descricao VARCHAR(255) DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sala_recursos (
  sala_id INT NOT NULL,
  recurso_id INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  PRIMARY KEY (sala_id, recurso_id),
  KEY fk_sala_recursos_recurso (recurso_id),
  CONSTRAINT fk_sala_recursos_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sala_recursos_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curso_modelos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  area_id INT DEFAULT NULL,
  nome VARCHAR(150) NOT NULL UNIQUE,
  carga_horaria_total INT NOT NULL DEFAULT 0,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_curso_modelos_area (area_id),
  CONSTRAINT fk_curso_modelos_area FOREIGN KEY (area_id) REFERENCES areas(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cursos_ofertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_modelo_id INT DEFAULT NULL,
  nome VARCHAR(150) NOT NULL,
  codigo_oferta VARCHAR(50) NOT NULL UNIQUE,
  hora_inicio TIME DEFAULT NULL,
  hora_fim TIME DEFAULT NULL,
  aula_segunda TINYINT(1) NOT NULL DEFAULT 1,
  aula_terca TINYINT(1) NOT NULL DEFAULT 1,
  aula_quarta TINYINT(1) NOT NULL DEFAULT 1,
  aula_quinta TINYINT(1) NOT NULL DEFAULT 1,
  aula_sexta TINYINT(1) NOT NULL DEFAULT 1,
  aula_sabado TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('Em andamento','Finalizada') NOT NULL DEFAULT 'Em andamento',
  descricao TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_cursos_ofertas_modelo (curso_modelo_id),
  CONSTRAINT fk_cursos_ofertas_modelo FOREIGN KEY (curso_modelo_id) REFERENCES curso_modelos(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS unidades_curriculares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_modelo_id INT NOT NULL,
  codigo VARCHAR(20) NOT NULL,
  nome VARCHAR(200) NOT NULL,
  carga_horaria INT NOT NULL,
  status ENUM('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uc_modelo_codigo (curso_modelo_id, codigo),
  CONSTRAINT fk_uc_curso_modelo FOREIGN KEY (curso_modelo_id) REFERENCES curso_modelos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS docentes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  horas_semanais INT NOT NULL DEFAULT 0,
  area_atuacao VARCHAR(100) NOT NULL,
  status ENUM('Ativo','Inativo') DEFAULT 'Ativo',
  observacoes TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_docentes_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS docente_cursos (
  docente_id INT NOT NULL,
  curso_id INT NOT NULL,
  PRIMARY KEY (docente_id, curso_id),
  KEY fk_docente_cursos_oferta (curso_id),
  CONSTRAINT fk_docente_cursos_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_docente_cursos_oferta FOREIGN KEY (curso_id) REFERENCES cursos_ofertas(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS docente_escala (
  id INT AUTO_INCREMENT PRIMARY KEY,
  docente_id INT NOT NULL,
  dia_semana ENUM('Segunda','Terça','Quarta','Quinta','Sexta','Sábado') NOT NULL,
  periodo ENUM('Manhã','Tarde','Noite') NOT NULL,
  horas INT NOT NULL,
  KEY fk_docente_escala_docente (docente_id),
  CONSTRAINT fk_docente_escala_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS docente_unidades_curriculares (
  docente_id INT NOT NULL,
  unidade_curricular_id INT NOT NULL,
  PRIMARY KEY (docente_id, unidade_curricular_id),
  KEY fk_docente_uc_uc (unidade_curricular_id),
  CONSTRAINT fk_docente_uc_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_docente_uc_uc FOREIGN KEY (unidade_curricular_id) REFERENCES unidades_curriculares(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aprendizagem_quadros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_oferta_id INT NOT NULL,
  unidade_curricular_id INT NOT NULL,
  sala_id INT NOT NULL,
  docente_id INT NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  observacoes TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_aprendizagem_turma (curso_oferta_id),
  KEY fk_aprendizagem_uc (unidade_curricular_id),
  KEY fk_aprendizagem_sala (sala_id),
  KEY fk_aprendizagem_docente (docente_id),
  CONSTRAINT fk_aprendizagem_turma FOREIGN KEY (curso_oferta_id) REFERENCES cursos_ofertas(id) ON UPDATE CASCADE,
  CONSTRAINT fk_aprendizagem_uc FOREIGN KEY (unidade_curricular_id) REFERENCES unidades_curriculares(id) ON UPDATE CASCADE,
  CONSTRAINT fk_aprendizagem_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON UPDATE CASCADE,
  CONSTRAINT fk_aprendizagem_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quadro_horario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aprendizagem_quadro_id INT DEFAULT NULL,
  curso_oferta_id INT NOT NULL,
  unidade_curricular_id INT NOT NULL,
  sala_id INT DEFAULT NULL,
  data_aula DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  divisao_por_hora TINYINT(1) NOT NULL DEFAULT 0,
  dupla_docencia TINYINT(1) NOT NULL DEFAULT 0,
  visita_tecnica TINYINT(1) NOT NULL DEFAULT 0,
  ead_assincrona TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('Ativa','Cancelada','Reposição') NOT NULL DEFAULT 'Ativa',
  observacoes TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY fk_quadro_aprendizagem (aprendizagem_quadro_id),
  KEY fk_quadro_curso_oferta (curso_oferta_id),
  KEY fk_quadro_uc (unidade_curricular_id),
  KEY fk_quadro_sala (sala_id),
  CONSTRAINT fk_quadro_aprendizagem FOREIGN KEY (aprendizagem_quadro_id) REFERENCES aprendizagem_quadros(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_quadro_curso_oferta FOREIGN KEY (curso_oferta_id) REFERENCES cursos_ofertas(id) ON UPDATE CASCADE,
  CONSTRAINT fk_quadro_uc FOREIGN KEY (unidade_curricular_id) REFERENCES unidades_curriculares(id) ON UPDATE CASCADE,
  CONSTRAINT fk_quadro_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quadro_horario_docentes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quadro_horario_id INT NOT NULL,
  docente_id INT NOT NULL,
  UNIQUE KEY uq_quadro_docente (quadro_horario_id, docente_id),
  KEY fk_quadro_docente (docente_id),
  CONSTRAINT fk_quadro_docente_horario FOREIGN KEY (quadro_horario_id) REFERENCES quadro_horario(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_quadro_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendario_bloqueios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data DATE NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  tipo ENUM('Feriado','Evento','Recesso','Outro') NOT NULL DEFAULT 'Evento',
  descricao TEXT DEFAULT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_calendario_bloqueios_status (status),
  KEY idx_calendario_bloqueios_data (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS educacao_corporativa_docentes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  docente_id INT NOT NULL,
  data DATE NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_educacao_corporativa_data (data),
  KEY idx_educacao_corporativa_docente_data (docente_id, data),
  CONSTRAINT fk_educacao_corporativa_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sala_reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sala_id INT NOT NULL,
  tipo ENUM('Reservada','Manutencao') NOT NULL DEFAULT 'Reservada',
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  solicitante_usuario_id INT DEFAULT NULL,
  solicitante VARCHAR(150) DEFAULT NULL,
  motivo VARCHAR(150) DEFAULT NULL,
  descricao TEXT DEFAULT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sala_reservas_periodo (sala_id, data_inicio, data_fim, hora_inicio, hora_fim, status),
  KEY idx_sala_reservas_solicitante (solicitante_usuario_id),
  CONSTRAINT fk_sala_reservas_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON UPDATE CASCADE,
  CONSTRAINT fk_sala_reservas_solicitante FOREIGN KEY (solicitante_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sala_trocas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quadro_horario_id INT NOT NULL,
  sala_origem_id INT DEFAULT NULL,
  sala_destino_id INT NOT NULL,
  motivo VARCHAR(255) DEFAULT NULL,
  usuario_id INT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sala_trocas_aula (quadro_horario_id),
  KEY idx_sala_trocas_salas (sala_origem_id, sala_destino_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sistema_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT DEFAULT NULL,
  usuario_nome VARCHAR(150) DEFAULT NULL,
  usuario_email VARCHAR(150) DEFAULT NULL,
  nivel_acesso VARCHAR(50) DEFAULT NULL,
  metodo VARCHAR(10) NOT NULL,
  pagina VARCHAR(80) NOT NULL,
  acao VARCHAR(80) NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  dados LONGTEXT DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  navegador VARCHAR(255) DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sistema_logs_usuario (usuario_id),
  KEY idx_sistema_logs_pagina_acao (pagina, acao),
  KEY idx_sistema_logs_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO areas (id, nome, status, criado_em, atualizado_em) VALUES
(1, 'Tecnologia', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(2, 'Saude', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(3, 'Aprendizagem', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(4, 'Gestao e Negocios', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(5, 'Beleza e Estetica', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), status = VALUES(status);

INSERT INTO recursos (id, nome, descricao, criado_em) VALUES
(1, 'Computadores', NULL, CURRENT_TIMESTAMP),
(2, 'Projetor', NULL, CURRENT_TIMESTAMP),
(3, 'Ar-condicionado', NULL, CURRENT_TIMESTAMP),
(4, 'Lousa Digital', NULL, CURRENT_TIMESTAMP),
(5, 'TV', NULL, CURRENT_TIMESTAMP),
(6, 'Impressora 3D', NULL, CURRENT_TIMESTAMP),
(7, 'Quadro branco', NULL, CURRENT_TIMESTAMP),
(8, 'Sistema de som', NULL, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), descricao = VALUES(descricao);

INSERT INTO usuarios (
  id,
  nome,
  email,
  senha,
  nivel_acesso,
  status,
  ultimo_login,
  criado_em,
  atualizado_em
) VALUES (
  1,
  'Administrador',
  'vitrineata@vitrineata.com.br',
  '$2y$10$KUdIGr8ADlkQkfsUZrVCfuQRueTH0rHluh3bakFrKQja9TNMJ19yK',
  'Admin',
  'Ativo',
  NULL,
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  nivel_acesso = VALUES(nivel_acesso),
  status = VALUES(status);

SET FOREIGN_KEY_CHECKS = 1;
