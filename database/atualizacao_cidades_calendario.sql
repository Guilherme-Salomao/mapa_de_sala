SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  status ENUM('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS sigha_add_column_if_missing $$
CREATE PROCEDURE sigha_add_column_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_column_name VARCHAR(64),
  IN p_column_definition TEXT,
  IN p_after_column VARCHAR(64)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND COLUMN_NAME = p_column_name
  ) THEN
    SET @sigha_sql = CONCAT(
      'ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ',
      p_column_definition,
      IF(p_after_column IS NULL OR p_after_column = '', '', CONCAT(' AFTER `', p_after_column, '`'))
    );
    PREPARE stmt FROM @sigha_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END $$

DROP PROCEDURE IF EXISTS sigha_add_index_if_missing $$
CREATE PROCEDURE sigha_add_index_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_index_name VARCHAR(64),
  IN p_columns VARCHAR(255)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND INDEX_NAME = p_index_name
  ) THEN
    SET @sigha_sql = CONCAT(
      'ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_columns, ')'
    );
    PREPARE stmt FROM @sigha_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END $$

DROP PROCEDURE IF EXISTS sigha_add_fk_if_missing $$
CREATE PROCEDURE sigha_add_fk_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_constraint_name VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND CONSTRAINT_NAME = p_constraint_name
  ) THEN
    SET @sigha_sql = CONCAT(
      'ALTER TABLE `', p_table_name, '` ADD CONSTRAINT `', p_constraint_name, '` ',
      p_definition
    );
    PREPARE stmt FROM @sigha_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END $$

DELIMITER ;

CALL sigha_add_column_if_missing('cursos_ofertas', 'cidade_id', 'INT DEFAULT NULL', 'curso_modelo_id');
CALL sigha_add_column_if_missing('calendario_bloqueios', 'cidade_id', 'INT DEFAULT NULL', 'id');

CALL sigha_add_index_if_missing('cursos_ofertas', 'fk_cursos_ofertas_cidade', '`cidade_id`');
CALL sigha_add_index_if_missing('calendario_bloqueios', 'fk_calendario_bloqueios_cidade', '`cidade_id`');

CALL sigha_add_fk_if_missing(
  'cursos_ofertas',
  'fk_cursos_ofertas_cidade',
  'FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON UPDATE CASCADE'
);
CALL sigha_add_fk_if_missing(
  'calendario_bloqueios',
  'fk_calendario_bloqueios_cidade',
  'FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON UPDATE CASCADE'
);

DROP PROCEDURE IF EXISTS sigha_add_fk_if_missing;
DROP PROCEDURE IF EXISTS sigha_add_index_if_missing;
DROP PROCEDURE IF EXISTS sigha_add_column_if_missing;
