SET NAMES utf8mb4;

ALTER TABLE curso_modelos
  MODIFY COLUMN carga_horaria_total DECIMAL(8,2) NOT NULL DEFAULT 0.00;

ALTER TABLE unidades_curriculares
  MODIFY COLUMN carga_horaria DECIMAL(8,2) NOT NULL;

UPDATE unidades_curriculares uc
INNER JOIN curso_modelos cm ON cm.id = uc.curso_modelo_id
SET uc.carga_horaria = cm.carga_horaria_total,
    uc.nome = cm.nome
WHERE cm.sem_uc = 1
  AND uc.codigo = 'TURMA';
