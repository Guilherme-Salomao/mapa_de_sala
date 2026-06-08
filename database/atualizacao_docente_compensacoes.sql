SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS docente_compensacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  docente_id INT NOT NULL,
  data_inicio DATE NOT NULL,
  data_fim DATE NOT NULL,
  observacoes TEXT DEFAULT NULL,
  status ENUM('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_docente_compensacoes_periodo (data_inicio, data_fim, status),
  KEY idx_docente_compensacoes_docente_periodo (docente_id, data_inicio, data_fim, status),
  CONSTRAINT fk_docente_compensacoes_docente
    FOREIGN KEY (docente_id) REFERENCES docentes(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
