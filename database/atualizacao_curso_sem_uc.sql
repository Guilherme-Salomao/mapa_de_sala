SET NAMES utf8mb4;

SET @coluna_sem_uc := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'curso_modelos'
    AND COLUMN_NAME = 'sem_uc'
);

SET @sql_sem_uc := IF(
  @coluna_sem_uc = 0,
  'ALTER TABLE curso_modelos ADD COLUMN sem_uc TINYINT(1) NOT NULL DEFAULT 0 AFTER carga_horaria_total',
  'DO 0'
);

PREPARE stmt_sem_uc FROM @sql_sem_uc;
EXECUTE stmt_sem_uc;
DEALLOCATE PREPARE stmt_sem_uc;

INSERT IGNORE INTO unidades_curriculares (
  curso_modelo_id,
  codigo,
  nome,
  carga_horaria,
  status
)
SELECT
  cm.id,
  'TURMA',
  cm.nome,
  cm.carga_horaria_total,
  'Ativa'
FROM curso_modelos cm
WHERE cm.sem_uc = 1;
