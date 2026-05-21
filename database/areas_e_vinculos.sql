CREATE TABLE IF NOT EXISTS areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  status ENUM('Ativa', 'Inativa') NOT NULL DEFAULT 'Ativa',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE curso_modelos
  ADD COLUMN area_id INT NULL AFTER id;

ALTER TABLE curso_modelos
  ADD CONSTRAINT fk_curso_modelos_area
  FOREIGN KEY (area_id) REFERENCES areas(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS usuario_areas (
  usuario_id INT NOT NULL,
  area_id INT NOT NULL,
  PRIMARY KEY (usuario_id, area_id),
  CONSTRAINT fk_usuario_areas_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_usuario_areas_area
    FOREIGN KEY (area_id) REFERENCES areas(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS docente_unidades_curriculares (
  docente_id INT NOT NULL,
  unidade_curricular_id INT NOT NULL,
  PRIMARY KEY (docente_id, unidade_curricular_id),
  CONSTRAINT fk_docente_uc_docente
    FOREIGN KEY (docente_id) REFERENCES docentes(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_docente_uc_uc
    FOREIGN KEY (unidade_curricular_id) REFERENCES unidades_curriculares(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
