SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Atualizacao segura do banco de producao.
-- Rode este script uma vez no phpMyAdmin antes de testar o login novamente.
-- Ele nao apaga dados existentes.

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

CALL sigha_add_column_if_missing('cursos_ofertas', 'integral', 'TINYINT(1) NOT NULL DEFAULT 0', 'codigo_oferta');
CALL sigha_add_column_if_missing('cursos_ofertas', 'hora_inicio_tarde', 'TIME DEFAULT NULL', 'hora_fim');
CALL sigha_add_column_if_missing('cursos_ofertas', 'hora_fim_tarde', 'TIME DEFAULT NULL', 'hora_inicio_tarde');
CALL sigha_add_column_if_missing('cursos_ofertas', 'participa_parada_pedagogica', 'TINYINT(1) NOT NULL DEFAULT 1', 'hora_fim_tarde');
CALL sigha_add_column_if_missing('calendario_bloqueios', 'data_fim', 'DATE DEFAULT NULL', 'data');

DROP PROCEDURE IF EXISTS sigha_add_column_if_missing;

-- O sistema atual nao usa mais o tipo Evento/Outro no calendario.
-- Para preservar os registros ja cadastrados, eles entram como Feriado.
UPDATE calendario_bloqueios
SET tipo = 'Feriado'
WHERE tipo IN ('Evento', 'Outro');

ALTER TABLE calendario_bloqueios
  MODIFY COLUMN tipo ENUM('Feriado','Recesso','Parada Pedagogica') NOT NULL DEFAULT 'Feriado';

-- Areas padrao que podem estar faltando no banco antigo.
INSERT INTO areas (nome, status, criado_em, atualizado_em) VALUES
('Saude', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Beleza e Estetica', 'Ativa', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE status = VALUES(status), atualizado_em = CURRENT_TIMESTAMP;

SET FOREIGN_KEY_CHECKS = 1;
