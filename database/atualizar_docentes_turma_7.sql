SET NAMES utf8mb4;

-- Script para vincular docentes nas aulas sem docente da turma 7.
-- 1) Preencha abaixo a relacao UC -> docente.
-- 2) Execute primeiro os SELECTs de conferencia.
-- 3) Se estiver correto, execute o INSERT.

SET @turma_id = 7;

DROP TEMPORARY TABLE IF EXISTS tmp_docentes_uc;

CREATE TEMPORARY TABLE tmp_docentes_uc (
  unidade_curricular_id INT NOT NULL,
  docente_id INT NOT NULL,
  PRIMARY KEY (unidade_curricular_id)
);

-- PREENCHA AQUI:
-- Substitua os exemplos pelos IDs corretos.
-- INSERT INTO tmp_docentes_uc (unidade_curricular_id, docente_id) VALUES
-- (101, 5),
-- (102, 5),
-- (103, 8);

-- Conferencia 1: aulas da turma 7 ainda sem docente.
SELECT
  qh.id AS aula_id,
  qh.data_aula,
  qh.hora_inicio,
  qh.hora_fim,
  qh.unidade_curricular_id,
  COALESCE(tuc.codigo, uc.codigo) AS codigo_uc,
  COALESCE(tuc.nome, uc.nome) AS nome_uc
FROM quadro_horario qh
LEFT JOIN quadro_horario_docentes qhd
  ON qhd.quadro_horario_id = qh.id
LEFT JOIN unidades_curriculares uc
  ON uc.id = qh.unidade_curricular_id
LEFT JOIN turma_unidades_curriculares tuc
  ON tuc.curso_oferta_id = qh.curso_oferta_id
  AND tuc.unidade_curricular_id = qh.unidade_curricular_id
WHERE qh.curso_oferta_id = @turma_id
  AND qh.status = 'Ativa'
  AND qhd.id IS NULL
ORDER BY qh.data_aula, qh.hora_inicio, qh.id;

-- Conferencia 2: mapa UC -> docente que sera usado.
SELECT
  m.unidade_curricular_id,
  COALESCE(tuc.codigo, uc.codigo) AS codigo_uc,
  COALESCE(tuc.nome, uc.nome) AS nome_uc,
  m.docente_id,
  u.nome AS docente
FROM tmp_docentes_uc m
LEFT JOIN unidades_curriculares uc
  ON uc.id = m.unidade_curricular_id
LEFT JOIN turma_unidades_curriculares tuc
  ON tuc.curso_oferta_id = @turma_id
  AND tuc.unidade_curricular_id = m.unidade_curricular_id
LEFT JOIN docentes d
  ON d.id = m.docente_id
LEFT JOIN usuarios u
  ON u.id = d.usuario_id
ORDER BY COALESCE(tuc.codigo, uc.codigo);

-- Conferencia 3: mapeamentos sem vinculo docente x UC.
-- Se retornar linhas, corrija o INSERT da tabela temporaria antes de continuar.
SELECT
  m.unidade_curricular_id,
  m.docente_id,
  u.nome AS docente
FROM tmp_docentes_uc m
LEFT JOIN docente_unidades_curriculares duc
  ON duc.unidade_curricular_id = m.unidade_curricular_id
  AND duc.docente_id = m.docente_id
LEFT JOIN docentes d
  ON d.id = m.docente_id
LEFT JOIN usuarios u
  ON u.id = d.usuario_id
WHERE duc.docente_id IS NULL;

-- Conferencia 4: conflitos de horario do docente.
-- Se retornar linhas, essas aulas nao devem ser inseridas para esse docente.
SELECT
  qh.id AS aula_sem_docente_id,
  qh.data_aula,
  qh.hora_inicio,
  qh.hora_fim,
  m.docente_id,
  u.nome AS docente,
  qh2.id AS aula_conflitante_id,
  co2.nome AS turma_conflitante,
  qh2.hora_inicio AS conflito_inicio,
  qh2.hora_fim AS conflito_fim
FROM quadro_horario qh
INNER JOIN tmp_docentes_uc m
  ON m.unidade_curricular_id = qh.unidade_curricular_id
INNER JOIN docentes d
  ON d.id = m.docente_id
INNER JOIN usuarios u
  ON u.id = d.usuario_id
INNER JOIN quadro_horario_docentes qhd2
  ON qhd2.docente_id = m.docente_id
INNER JOIN quadro_horario qh2
  ON qh2.id = qhd2.quadro_horario_id
  AND qh2.status = 'Ativa'
  AND qh2.data_aula = qh.data_aula
  AND qh2.hora_inicio < qh.hora_fim
  AND qh2.hora_fim > qh.hora_inicio
INNER JOIN cursos_ofertas co2
  ON co2.id = qh2.curso_oferta_id
LEFT JOIN quadro_horario_docentes qhd_atual
  ON qhd_atual.quadro_horario_id = qh.id
WHERE qh.curso_oferta_id = @turma_id
  AND qh.status = 'Ativa'
  AND qhd_atual.id IS NULL
ORDER BY qh.data_aula, qh.hora_inicio;

-- Insercao segura:
-- Insere docente apenas em aulas sem docente, com vinculo na UC e sem conflito de horario.
START TRANSACTION;

INSERT INTO quadro_horario_docentes (quadro_horario_id, docente_id)
SELECT
  qh.id,
  m.docente_id
FROM quadro_horario qh
INNER JOIN tmp_docentes_uc m
  ON m.unidade_curricular_id = qh.unidade_curricular_id
INNER JOIN docente_unidades_curriculares duc
  ON duc.unidade_curricular_id = qh.unidade_curricular_id
  AND duc.docente_id = m.docente_id
LEFT JOIN quadro_horario_docentes qhd_atual
  ON qhd_atual.quadro_horario_id = qh.id
WHERE qh.curso_oferta_id = @turma_id
  AND qh.status = 'Ativa'
  AND qhd_atual.id IS NULL
  AND NOT EXISTS (
    SELECT 1
    FROM quadro_horario_docentes qhd2
    INNER JOIN quadro_horario qh2
      ON qh2.id = qhd2.quadro_horario_id
    WHERE qhd2.docente_id = m.docente_id
      AND qh2.status = 'Ativa'
      AND qh2.data_aula = qh.data_aula
      AND qh2.hora_inicio < qh.hora_fim
      AND qh2.hora_fim > qh.hora_inicio
  );

SET @registros_inseridos = ROW_COUNT();

COMMIT;

SELECT @registros_inseridos AS docentes_inseridos;

-- Conferencia final: aulas que continuam sem docente.
SELECT
  qh.id AS aula_id,
  qh.data_aula,
  qh.hora_inicio,
  qh.hora_fim,
  qh.unidade_curricular_id,
  COALESCE(tuc.codigo, uc.codigo) AS codigo_uc,
  COALESCE(tuc.nome, uc.nome) AS nome_uc
FROM quadro_horario qh
LEFT JOIN quadro_horario_docentes qhd
  ON qhd.quadro_horario_id = qh.id
LEFT JOIN unidades_curriculares uc
  ON uc.id = qh.unidade_curricular_id
LEFT JOIN turma_unidades_curriculares tuc
  ON tuc.curso_oferta_id = qh.curso_oferta_id
  AND tuc.unidade_curricular_id = qh.unidade_curricular_id
WHERE qh.curso_oferta_id = @turma_id
  AND qh.status = 'Ativa'
  AND qhd.id IS NULL
ORDER BY qh.data_aula, qh.hora_inicio, qh.id;
