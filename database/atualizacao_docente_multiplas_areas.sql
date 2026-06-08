SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS docente_areas (
  docente_id INT NOT NULL,
  area_id INT NOT NULL,
  PRIMARY KEY (docente_id, area_id),
  KEY fk_docente_areas_area (area_id),
  CONSTRAINT fk_docente_areas_docente FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_docente_areas_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO docente_areas (docente_id, area_id)
SELECT d.id, a.id
FROM docentes d
INNER JOIN areas a ON a.nome = d.area_atuacao
WHERE d.area_atuacao IS NOT NULL
  AND d.area_atuacao <> '';

SET FOREIGN_KEY_CHECKS = 1;
