SET NAMES utf8mb4;

-- Atualizacao para permitir bloqueio parcial no calendario.
-- Mantem os registros atuais como "dia inteiro" quando hora_inicio/hora_fim ficarem NULL.

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

DELIMITER ;

CALL sigha_add_column_if_missing('calendario_bloqueios', 'hora_inicio', 'TIME DEFAULT NULL', 'data_fim');
CALL sigha_add_column_if_missing('calendario_bloqueios', 'hora_fim', 'TIME DEFAULT NULL', 'hora_inicio');

DROP PROCEDURE IF EXISTS sigha_add_column_if_missing;
