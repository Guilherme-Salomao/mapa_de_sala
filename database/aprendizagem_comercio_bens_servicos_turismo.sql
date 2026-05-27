SET NAMES utf8mb4;
START TRANSACTION;

SET @area_id := (
  SELECT id
  FROM areas
  WHERE nome = 'Aprendizagem'
  LIMIT 1
);

INSERT INTO curso_modelos (
  area_id,
  nome,
  carga_horaria_total,
  status
) VALUES (
  @area_id,
  'Aprendizagem Profissional em Comércio de Bens, Serviços e Turismo',
  500,
  'Ativo'
)
ON DUPLICATE KEY UPDATE
  id = LAST_INSERT_ID(id),
  area_id = VALUES(area_id),
  carga_horaria_total = VALUES(carga_horaria_total),
  status = VALUES(status);

SET @curso_modelo_id := LAST_INSERT_ID();

INSERT INTO unidades_curriculares (
  curso_modelo_id,
  codigo,
  nome,
  carga_horaria,
  status
) VALUES
(@curso_modelo_id, 'UC01', 'Ambientação Profissional', 40, 'Ativa'),
(@curso_modelo_id, 'UC02', 'Organizações Empresariais no Mundo do Trabalho', 45, 'Ativa'),
(@curso_modelo_id, 'UC03', 'Fluência e Cidadania Digital para o Trabalho', 30, 'Ativa'),
(@curso_modelo_id, 'UC04', 'Sala de Ocupações: Gestão e Negócios', 30, 'Ativa'),
(@curso_modelo_id, 'UC05', 'Autocuidado e Qualidade de Vida para a Juventude', 35, 'Ativa'),
(@curso_modelo_id, 'UC06', 'Cidadania, Participação e Políticas Públicas para a Juventude', 35, 'Ativa'),
(@curso_modelo_id, 'UC07', 'Tendências e Desafios do Mundo do Trabalho', 40, 'Ativa'),
(@curso_modelo_id, 'UC08', 'Sala de Ocupações: Turismo, Hospitalidade e Lazer e Infraestrutura', 30, 'Ativa'),
(@curso_modelo_id, 'UC09', 'Identidade e Relações Interpessoais', 40, 'Ativa'),
(@curso_modelo_id, 'UC10', 'Direito à Cidade e à Cultura', 35, 'Ativa'),
(@curso_modelo_id, 'UC11', 'Projeto Aprendizagem - Plano de Desenvolvimento Pessoal e Profissional', 40, 'Ativa'),
(@curso_modelo_id, 'UC12', 'Prática Profissional da Aprendizagem em Comércio de Bens, Serviços e Turismo', 100, 'Ativa')
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  carga_horaria = VALUES(carga_horaria),
  status = VALUES(status);

COMMIT;
