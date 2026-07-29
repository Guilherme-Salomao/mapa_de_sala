SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS docente_substituicoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  docente_id INT NOT NULL,
  sala_id INT DEFAULT NULL,
  data_aula DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  turma VARCHAR(150) NOT NULL,
  unidade_curricular VARCHAR(200) DEFAULT NULL,
  motivo VARCHAR(150) DEFAULT NULL,
  observacoes TEXT DEFAULT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_docente_substituicoes_docente_data (docente_id, data_aula, hora_inicio, hora_fim, status),
  KEY idx_docente_substituicoes_sala_data (sala_id, data_aula, hora_inicio, hora_fim, status),
  CONSTRAINT fk_docente_substituicoes_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_docente_substituicoes_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
