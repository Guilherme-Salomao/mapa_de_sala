SET NAMES utf8mb4;

ALTER TABLE cursos_ofertas
  MODIFY COLUMN participa_recesso_escolar TINYINT(1) NOT NULL DEFAULT 1;
