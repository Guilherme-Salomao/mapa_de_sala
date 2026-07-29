SET NAMES utf8mb4;

-- Mantem os logs dos ultimos 365 dias.
-- Execute este script periodicamente, de preferencia uma vez por mes.
SET @dias_retencao = 10;
SET @data_limite = DATE_SUB(CURRENT_DATE, INTERVAL @dias_retencao DAY);

SELECT
  @data_limite AS logs_anteriores_a,
  COUNT(*) AS registros_para_excluir
FROM sistema_logs
WHERE criado_em < @data_limite;

START TRANSACTION;

DELETE FROM sistema_logs
WHERE criado_em < @data_limite;

SET @registros_excluidos = ROW_COUNT();

COMMIT;

SELECT
  @registros_excluidos AS registros_excluidos,
  COUNT(*) AS registros_mantidos
FROM sistema_logs;

-- Para apagar todos os logs manualmente, utilize somente quando necessario:
-- TRUNCATE TABLE sistema_logs;
