SET NAMES utf8mb4;

-- Diagnostico de dependencias para exclusao de docente.
-- Troque o valor abaixo pelo ID do docente que deseja verificar.
SET @docente_id := 0;

SELECT
  d.id AS docente_id,
  u.nome AS docente,
  u.email,
  d.status
FROM docentes d
LEFT JOIN usuarios u ON u.id = d.usuario_id
WHERE d.id = @docente_id;

SELECT 'docente_areas' AS tabela, COUNT(*) AS total
FROM docente_areas
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_cursos' AS tabela, COUNT(*) AS total
FROM docente_cursos
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_escala' AS tabela, COUNT(*) AS total
FROM docente_escala
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_unidades_curriculares' AS tabela, COUNT(*) AS total
FROM docente_unidades_curriculares
WHERE docente_id = @docente_id
UNION ALL
SELECT 'quadro_horario_docentes' AS tabela, COUNT(*) AS total
FROM quadro_horario_docentes
WHERE docente_id = @docente_id
UNION ALL
SELECT 'educacao_corporativa_docentes' AS tabela, COUNT(*) AS total
FROM educacao_corporativa_docentes
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_substituicoes' AS tabela, COUNT(*) AS total
FROM docente_substituicoes
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_ferias' AS tabela, COUNT(*) AS total
FROM docente_ferias
WHERE docente_id = @docente_id
UNION ALL
SELECT 'docente_compensacoes' AS tabela, COUNT(*) AS total
FROM docente_compensacoes
WHERE docente_id = @docente_id
UNION ALL
SELECT 'aprendizagem_quadros' AS tabela, COUNT(*) AS total
FROM aprendizagem_quadros
WHERE docente_id = @docente_id;

-- Aulas lancadas no quadro horario para este docente.
SELECT
  qhd.id AS vinculo_id,
  qh.id AS aula_id,
  qh.data_aula,
  qh.hora_inicio,
  qh.hora_fim,
  co.nome AS turma,
  uc.codigo AS uc_codigo,
  uc.nome AS unidade_curricular,
  s.nome AS sala,
  qh.status
FROM quadro_horario_docentes qhd
INNER JOIN quadro_horario qh ON qh.id = qhd.quadro_horario_id
LEFT JOIN cursos_ofertas co ON co.id = qh.curso_oferta_id
LEFT JOIN unidades_curriculares uc ON uc.id = qh.unidade_curricular_id
LEFT JOIN salas s ON s.id = qh.sala_id
WHERE qhd.docente_id = @docente_id
ORDER BY qh.data_aula, qh.hora_inicio, qh.id;

-- Substituicoes cadastradas.
SELECT
  ds.id,
  ds.data_aula,
  ds.hora_inicio,
  ds.hora_fim,
  ds.turma,
  ds.unidade_curricular,
  s.nome AS sala,
  ds.status
FROM docente_substituicoes ds
LEFT JOIN salas s ON s.id = ds.sala_id
WHERE ds.docente_id = @docente_id
ORDER BY ds.data_aula, ds.hora_inicio, ds.id;

-- Educacao corporativa / cursos vinculados ao docente.
SELECT
  ec.id,
  ec.data,
  ec.dia_inteiro,
  ec.hora_inicio,
  ec.hora_fim,
  ec.titulo,
  ec.status
FROM educacao_corporativa_docentes ec
WHERE ec.docente_id = @docente_id
ORDER BY ec.data, ec.hora_inicio, ec.id;

-- Ferias do docente.
SELECT
  df.id,
  df.data_inicio,
  df.data_fim,
  df.observacoes,
  df.status
FROM docente_ferias df
WHERE df.docente_id = @docente_id
ORDER BY df.data_inicio, df.data_fim, df.id;

-- Compensacoes do docente.
SELECT
  dc.id,
  dc.data_inicio,
  dc.data_fim,
  dc.hora_inicio,
  dc.hora_fim,
  dc.horas,
  dc.observacoes,
  dc.status
FROM docente_compensacoes dc
WHERE dc.docente_id = @docente_id
ORDER BY dc.data_inicio, dc.data_fim, dc.hora_inicio, dc.id;

-- Escala semanal do docente.
SELECT
  de.id,
  de.dia_semana,
  de.periodo,
  de.horas
FROM docente_escala de
WHERE de.docente_id = @docente_id
ORDER BY FIELD(de.dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'),
         FIELD(de.periodo, 'Manhã', 'Tarde', 'Noite'),
         de.id;

-- Areas vinculadas.
SELECT
  da.docente_id,
  a.id AS area_id,
  a.nome AS area
FROM docente_areas da
INNER JOIN areas a ON a.id = da.area_id
WHERE da.docente_id = @docente_id
ORDER BY a.nome;

-- Unidades curriculares vinculadas.
SELECT
  duc.docente_id,
  uc.id AS unidade_curricular_id,
  uc.codigo,
  uc.nome
FROM docente_unidades_curriculares duc
INNER JOIN unidades_curriculares uc ON uc.id = duc.unidade_curricular_id
WHERE duc.docente_id = @docente_id
ORDER BY uc.codigo, uc.nome;

-- Cursos/ofertas vinculados diretamente ao docente, caso exista uso dessa tabela no banco.
SELECT
  dc.docente_id,
  co.id AS curso_oferta_id,
  co.nome,
  co.codigo_oferta,
  co.status
FROM docente_cursos dc
INNER JOIN cursos_ofertas co ON co.id = dc.curso_id
WHERE dc.docente_id = @docente_id
ORDER BY co.nome;

-- Quadros de aprendizagem com docente preferencial.
SELECT
  aq.id,
  aq.data_inicio,
  aq.data_fim,
  co.nome AS turma,
  uc.codigo AS uc_codigo,
  uc.nome AS unidade_curricular,
  aq.status
FROM aprendizagem_quadros aq
LEFT JOIN cursos_ofertas co ON co.id = aq.curso_oferta_id
LEFT JOIN unidades_curriculares uc ON uc.id = aq.unidade_curricular_id
WHERE aq.docente_id = @docente_id
ORDER BY aq.data_inicio, aq.id;

