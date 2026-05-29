SET NAMES utf8mb4;

SET @coluna_existe := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cursos_ofertas'
    AND COLUMN_NAME = 'participa_recesso_escolar'
);

SET @sql := IF(
  @coluna_existe = 0,
  'ALTER TABLE cursos_ofertas ADD COLUMN participa_recesso_escolar TINYINT(1) NOT NULL DEFAULT 0 AFTER participa_parada_pedagogica',
  'SELECT ''Coluna participa_recesso_escolar ja existe.'' AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
