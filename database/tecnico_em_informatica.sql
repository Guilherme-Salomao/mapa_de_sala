SET NAMES utf8mb4;
START TRANSACTION;

SET @area_id := (
  SELECT id
  FROM areas
  WHERE nome = 'Tecnologia'
  LIMIT 1
);

INSERT INTO curso_modelos (
  area_id,
  nome,
  carga_horaria_total,
  status
) VALUES (
  @area_id,
  'Técnico em Informática',
  1200,
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
(@curso_modelo_id, 'UC01', 'Planejar e executar a montagem de computadores', 84, 'Ativa'),
(@curso_modelo_id, 'UC02', 'Planejar e executar a instalação de hardware e software para computadores', 96, 'Ativa'),
(@curso_modelo_id, 'UC03', 'Planejar e executar a manutenção de computadores', 72, 'Ativa'),
(@curso_modelo_id, 'UC04', 'Projeto Integrador Assistente de Suporte e Manutenção de Computadores', 20, 'Ativa'),
(@curso_modelo_id, 'UC05', 'Planejar e executar a instalação de redes locais de computadores', 96, 'Ativa'),
(@curso_modelo_id, 'UC06', 'Planejar e executar a manutenção de redes locais de computadores', 96, 'Ativa'),
(@curso_modelo_id, 'UC07', 'Planejar e executar a instalação, a configuração e o monitoramento de sistemas operacionais de redes locais (servidores)', 96, 'Ativa'),
(@curso_modelo_id, 'UC08', 'Projeto Integrador Assistente de Operação de Redes de Computadores', 20, 'Ativa'),
(@curso_modelo_id, 'UC09', 'Desenvolver Algoritmos', 108, 'Ativa'),
(@curso_modelo_id, 'UC10', 'Desenvolver banco de dados', 72, 'Ativa'),
(@curso_modelo_id, 'UC11', 'Executar teste e implantação de aplicativos computacionais', 60, 'Ativa'),
(@curso_modelo_id, 'UC12', 'Executar os processos de codificação, manutenção e documentação de aplicativos computacionais para desktop', 96, 'Ativa'),
(@curso_modelo_id, 'UC13', 'Executar os processos de codificação, manutenção e documentação de aplicativos computacionais para internet', 96, 'Ativa'),
(@curso_modelo_id, 'UC14', 'Manipular e otimizar imagens vetoriais, bitmaps gráficos e elementos visuais de navegação para web', 48, 'Ativa'),
(@curso_modelo_id, 'UC15', 'Desenvolver e organizar elementos estruturais de sites', 108, 'Ativa'),
(@curso_modelo_id, 'UC16', 'Projeto Integrador Assistente de Desenvolvimento de Aplicativos Computacionais', 32, 'Ativa')
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  carga_horaria = VALUES(carga_horaria),
  status = VALUES(status);

COMMIT;
