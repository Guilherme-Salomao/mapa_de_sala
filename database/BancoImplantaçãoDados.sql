SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Script de dados extraido da producao.
-- Importe apos criar a estrutura com BancoImplantacao.sql ou BancoImplantação.sql.
-- Usa INSERT IGNORE para evitar sobrescrever registros ja existentes.

-- Dados: areas
INSERT IGNORE INTO `areas` (`id`, `nome`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 'Tecnologia', 'Ativa', '2026-05-26 01:12:14', '2026-05-26 01:12:14'),
(2, 'Aprendizagem', 'Ativa', '2026-05-26 01:12:14', '2026-05-26 01:12:14'),
(3, 'Gestao e Negocios', 'Ativa', '2026-05-26 01:12:14', '2026-05-26 01:12:14'),
(4, 'Saude', 'Ativa', '2026-05-28 00:04:21', '2026-05-28 00:04:21'),
(5, 'Beleza e Estetica', 'Ativa', '2026-05-28 00:04:21', '2026-05-28 00:04:21');

-- Dados: usuarios
INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `status`, `ultimo_login`, `criado_em`, `atualizado_em`) VALUES
(1, 'Salomão', 'vitrineata@vitrineata.com.br', '$2y$10$YXaB/GHclyWjwiFq72Bp8Otl8jp.4vWE883y1r/PxGVaY6uTZpKLK', 'Admin', 'Ativo', '2026-05-28 14:17:23', '2026-05-25 22:12:14', '2026-05-28 14:17:23'),
(3, 'Guilherme Salomão Shorane', 'guilherme.sshorane@sp.senac.br', '$2y$10$jkJxQ/fZinLFQvv1D0RVXOAP64rgWlovBqAU40pU12z1zXFxxWSZG', 'Professor', 'Ativo', '2026-05-26 14:20:38', '2026-05-26 13:51:35', '2026-05-26 14:20:38'),
(4, 'Isabella Celestrino', 'isabellafcelestrino@gmail.com', '$2y$10$NFan1xteyubVk95QBERJ5eYFKuutaJGZ4IAbsIKF2HOgFPjV05k5q', 'Professor', 'Ativo', '2026-05-27 16:27:33', '2026-05-26 22:56:05', '2026-05-27 16:27:33'),
(5, 'Heloisa Cancian Garcia', 'heloisa.cgarcia@sp.senac.br', '$2y$10$axCBmPeD0OgGG7pCl9UCYOuPc5s3rSuBe01PPWKlSSmEs7rmnCuZ.', 'Professor', 'Ativo', '2026-05-27 22:05:19', '2026-05-27 22:02:45', '2026-05-27 22:05:19');

-- Dados: recursos
INSERT IGNORE INTO `recursos` (`id`, `nome`, `descricao`, `criado_em`) VALUES
(1, 'Computadores', NULL, '2026-05-26 01:12:14'),
(2, 'Projetor', NULL, '2026-05-26 01:12:14'),
(3, 'Ar-condicionado', NULL, '2026-05-26 01:12:14'),
(4, 'Lousa Digital', NULL, '2026-05-26 01:12:14'),
(5, 'TV', NULL, '2026-05-26 01:12:14'),
(6, 'Impressora 3D', NULL, '2026-05-26 01:12:14'),
(7, 'Quadro branco', NULL, '2026-05-26 01:12:14'),
(8, 'Sistema de som', NULL, '2026-05-26 01:12:14');

-- Dados: salas
INSERT IGNORE INTO `salas` (`id`, `nome`, `tipo`, `capacidade`, `status`, `descricao`, `criado_em`, `atualizado_em`) VALUES
(1, 'Laboratório 01', 'Laboratório de Informática', 18, 'ativa', 'Exclusivo para Técnico em Redes', '2026-05-26 17:03:43', '2026-05-26 17:03:43'),
(2, 'Laboratório 02', 'Laboratório de Informática', 20, 'ativa', '', '2026-05-26 17:04:11', '2026-05-26 17:04:11'),
(3, 'Laboratório 03', 'Laboratório de Informática', 30, 'ativa', '', '2026-05-27 19:40:15', '2026-05-27 19:40:15');

-- Dados: sala_recursos
INSERT IGNORE INTO `sala_recursos` (`sala_id`, `recurso_id`, `quantidade`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 1),
(1, 7, 1),
(1, 8, 1),
(2, 1, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 1),
(2, 7, 1),
(2, 8, 1),
(3, 1, 1),
(3, 2, 1),
(3, 3, 1),
(3, 4, 1),
(3, 7, 1),
(3, 8, 1);

-- Dados: curso_modelos
INSERT IGNORE INTO `curso_modelos` (`id`, `area_id`, `nome`, `carga_horaria_total`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'Habilitação Profissional Técnica em Informática', 1200, 'Ativo', '2026-05-26 16:53:43', '2026-05-26 16:53:43'),
(2, 2, 'Aprendizagem Profissional em Comércio de bens, Serviços e Turismo', 500, 'Ativo', '2026-05-26 17:06:49', '2026-05-26 17:06:49'),
(3, 1, 'Habilitação Profissional Técnica em Informática para Internet', 1000, 'Ativo', '2026-05-27 19:28:59', '2026-05-27 19:28:59');

-- Dados: cursos_ofertas
INSERT IGNORE INTO `cursos_ofertas` (`id`, `curso_modelo_id`, `nome`, `codigo_oferta`, `integral`, `hora_inicio`, `hora_fim`, `hora_inicio_tarde`, `hora_fim_tarde`, `participa_parada_pedagogica`, `aula_segunda`, `aula_terca`, `aula_quarta`, `aula_quinta`, `aula_sexta`, `aula_sabado`, `status`, `descricao`, `criado_em`, `atualizado_em`) VALUES
(4, 3, 'Técnico Internet 2026', '9900357560', 0, '19:00:00', '22:30:00', NULL, NULL, 1, 1, 1, 1, 1, 1, 0, 'Em andamento', 'Docente Principal Isabella', '2026-05-27 19:37:40', '2026-05-27 19:37:40'),
(5, 2, '2025 A Bir', '9900319736', 0, '13:30:00', '17:30:00', NULL, NULL, 0, 0, 1, 0, 0, 0, 0, 'Em andamento', 'Docente Principal: Heloisa', '2026-05-28 01:09:13', '2026-05-28 01:09:13');

-- Dados: unidades_curriculares
INSERT IGNORE INTO `unidades_curriculares` (`id`, `curso_modelo_id`, `codigo`, `nome`, `carga_horaria`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'UC01', 'Planejar e executar a montagem de computadores', 84, 'Ativa', '2026-05-26 16:55:07', '2026-05-26 16:55:07'),
(2, 1, 'UC02', 'Planejar e executar a instalação de hardware e software para computadores', 96, 'Ativa', '2026-05-26 16:55:21', '2026-05-26 16:55:21'),
(3, 1, 'UC03', 'Planejar e executar a manutenção de computadores', 72, 'Ativa', '2026-05-26 16:55:42', '2026-05-26 16:55:42'),
(4, 1, 'UC04', 'Projeto Integrador Assistente de Suporte e Manutenção de Computadores', 20, 'Ativa', '2026-05-26 16:56:24', '2026-05-26 16:56:24'),
(5, 1, 'UC05', 'Planejar e executar a instalação de redes locais de computadores', 96, 'Ativa', '2026-05-26 16:56:38', '2026-05-26 16:56:38'),
(6, 1, 'UC06', 'Planejar e executar a manutenção de redes locais de computadores', 96, 'Ativa', '2026-05-26 16:56:49', '2026-05-26 16:56:49'),
(7, 1, 'UC07', 'Planejar e executar a instalação, a configuração e o monitoramento de sistemas operacionais de redes locais (servidores)', 96, 'Ativa', '2026-05-26 16:57:06', '2026-05-26 16:57:06'),
(8, 1, 'UC08', 'Projeto Integrador Assistente de Operação de Redes de Computadores', 20, 'Ativa', '2026-05-26 16:57:31', '2026-05-26 16:57:31'),
(9, 1, 'UC09', 'Desenvolver Algoritmos', 108, 'Ativa', '2026-05-26 16:57:49', '2026-05-26 16:57:49'),
(10, 1, 'UC10', 'Desenvolver banco de dados', 72, 'Ativa', '2026-05-26 16:58:03', '2026-05-26 16:58:03'),
(11, 1, 'UC11', 'Executar teste e implantação de aplicativos computacionais', 60, 'Ativa', '2026-05-26 16:58:21', '2026-05-26 16:58:21'),
(12, 1, 'UC12', 'Executar os processos de codificação, manutenção e documentação de aplicativos computacionais para desktop', 96, 'Ativa', '2026-05-26 16:58:36', '2026-05-26 16:58:36'),
(13, 1, 'UC13', 'Executar os processos de codificação, manutenção e documentação de aplicativos computacionais para internet', 96, 'Ativa', '2026-05-26 16:58:56', '2026-05-26 16:58:56'),
(14, 1, 'UC14', 'Manipular e otimizar imagens vetoriais, bitmaps gráficos e elementos visuais de navegação para web', 48, 'Ativa', '2026-05-26 16:59:13', '2026-05-26 16:59:13'),
(15, 1, 'UC15', 'Desenvolver e organizar elementos estruturais de sites', 108, 'Ativa', '2026-05-26 16:59:28', '2026-05-26 16:59:28'),
(16, 1, 'UC16', 'Projeto Integrador Assistente de Desenvolvimento de Aplicativos Computacionais', 32, 'Ativa', '2026-05-26 16:59:46', '2026-05-26 16:59:46'),
(17, 2, 'UC01', 'Ambientação Profissional', 40, 'Ativa', '2026-05-26 17:07:21', '2026-05-26 17:07:21'),
(18, 2, 'UC02', 'Organizações Empresariais no Mundo do Trabalho', 45, 'Ativa', '2026-05-26 17:07:46', '2026-05-26 17:07:46'),
(19, 2, 'UC03', 'Fluência e Cidadania Digital para o Trabalho', 30, 'Ativa', '2026-05-26 17:08:31', '2026-05-26 17:08:31'),
(20, 2, 'UC04', 'Sala de Ocupações: Gestão e Negócios', 30, 'Ativa', '2026-05-26 17:08:43', '2026-05-26 17:08:43'),
(21, 2, 'UC05', 'Autocuidado e Qualidade de Vida para a Juventude', 35, 'Ativa', '2026-05-26 17:08:57', '2026-05-26 17:08:57'),
(22, 2, 'UC06', 'Cidadania, Participação e Políticas Públicas para a Juventude', 35, 'Ativa', '2026-05-26 17:09:10', '2026-05-26 17:09:10'),
(23, 2, 'UC07', 'Tendências e Desafios do Mundo do Trabalho', 40, 'Ativa', '2026-05-26 17:09:23', '2026-05-26 17:09:23'),
(24, 2, 'UC08', 'Sala de Ocupações: Turismo, Hospitalidade e Lazer e Infraestrutura', 30, 'Ativa', '2026-05-26 17:09:37', '2026-05-26 17:09:37'),
(25, 2, 'UC09', 'Identidade e Relações Interpessoais', 40, 'Ativa', '2026-05-26 17:09:49', '2026-05-26 17:09:49'),
(26, 2, 'UC10', 'Direito à Cidade e à Cultura', 35, 'Ativa', '2026-05-26 17:10:03', '2026-05-26 17:10:03'),
(27, 2, 'UC11', 'Projeto Aprendizagem-Plano de Desenvolvimento Pessoal e Profissional', 40, 'Ativa', '2026-05-26 17:10:19', '2026-05-26 17:10:19'),
(28, 2, 'UC12', 'Prática Profissional Aprendizagem em Comércio de bens e Turismo', 100, 'Ativa', '2026-05-26 17:10:29', '2026-05-26 17:10:29'),
(29, 3, 'UC01', 'Elaborar projetos de aplicações para web', 36, 'Ativa', '2026-05-27 19:30:09', '2026-05-27 19:30:09'),
(30, 3, 'UC02', 'Desenvolver aplicações para websites', 72, 'Ativa', '2026-05-27 19:30:32', '2026-05-27 19:30:32'),
(31, 3, 'UC03', 'Codificar front-end de aplicações web', 96, 'Ativa', '2026-05-27 19:31:04', '2026-05-27 19:31:04'),
(32, 3, 'UC04', 'Publicar aplicações web', 36, 'Ativa', '2026-05-27 19:31:19', '2026-05-27 19:31:19'),
(33, 3, 'UC05', 'Projeto Integrador Desenvolvedor Front-End', 24, 'Ativa', '2026-05-27 19:31:40', '2026-05-27 19:31:40'),
(34, 3, 'UC06', 'Desenvolver algoritmos', 96, 'Ativa', '2026-05-27 19:32:08', '2026-05-27 19:32:08'),
(35, 3, 'UC07', 'Codificar back-end de aplicações web', 120, 'Ativa', '2026-05-27 19:32:27', '2026-05-27 19:39:02'),
(36, 3, 'UC08', 'Implementar banco de dados para web', 84, 'Ativa', '2026-05-27 19:32:45', '2026-05-27 19:32:45'),
(37, 3, 'UC09', 'Desenvolver serviços web', 48, 'Ativa', '2026-05-27 19:33:05', '2026-05-27 19:33:05'),
(38, 3, 'UC10', 'Organizar o processo de trabalho no desenvolvimento de aplicações', 48, 'Ativa', '2026-05-27 19:33:24', '2026-05-27 19:33:24'),
(39, 3, 'UC11', 'Projeto Integrador Desenvolvedor Back-End', 32, 'Ativa', '2026-05-27 19:33:42', '2026-05-27 19:33:42'),
(40, 3, 'UC12', 'Desenvolver interface gráfica para dispositivos móveis', 60, 'Ativa', '2026-05-27 19:34:05', '2026-05-27 19:34:05'),
(41, 3, 'UC13', 'Codificar aplicações para dispositivos móveis', 120, 'Ativa', '2026-05-27 19:34:24', '2026-05-27 19:34:24'),
(42, 3, 'UC14', 'Codificar acesso à web services e recursos de sistemas móveis', 60, 'Ativa', '2026-05-27 19:35:01', '2026-05-27 19:35:01'),
(43, 3, 'UC15', 'Publicar aplicações para dispositivos móveis', 36, 'Ativa', '2026-05-27 19:35:17', '2026-05-27 19:35:17'),
(44, 3, 'UC16', 'Projeto Integrador Desenvolvedor Mobile', 32, 'Ativa', '2026-05-27 19:35:39', '2026-05-27 19:35:39');

-- Dados: docentes
INSERT IGNORE INTO `docentes` (`id`, `usuario_id`, `horas_semanais`, `area_atuacao`, `status`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(2, 3, 40, 'Tecnologia', 'Ativo', '', '2026-05-26 16:52:03', '2026-05-26 16:52:03'),
(3, 4, 32, 'Tecnologia', 'Ativo', '', '2026-05-27 19:26:58', '2026-05-27 19:26:58'),
(4, 5, 30, 'Aprendizagem', 'Ativo', '', '2026-05-28 01:04:48', '2026-05-28 01:04:48');

-- Dados: docente_areas
INSERT IGNORE INTO docente_areas (docente_id, area_id)
SELECT d.id, a.id
FROM docentes d
INNER JOIN areas a ON a.nome = d.area_atuacao
WHERE d.area_atuacao IS NOT NULL
  AND d.area_atuacao <> '';

-- Dados: docente_escala
INSERT IGNORE INTO `docente_escala` (`id`, `docente_id`, `dia_semana`, `periodo`, `horas`) VALUES
(31, 2, 'Segunda', 'Tarde', 4),
(32, 2, 'Segunda', 'Noite', 4),
(33, 2, 'Terça', 'Tarde', 4),
(34, 2, 'Terça', 'Noite', 4),
(35, 2, 'Quarta', 'Tarde', 4),
(36, 2, 'Quarta', 'Noite', 4),
(37, 2, 'Quinta', 'Tarde', 4),
(38, 2, 'Quinta', 'Noite', 4),
(39, 2, 'Sexta', 'Tarde', 4),
(40, 2, 'Sexta', 'Noite', 4),
(49, 3, 'Segunda', 'Tarde', 4),
(50, 3, 'Segunda', 'Noite', 4),
(51, 3, 'Terça', 'Noite', 4),
(52, 3, 'Quarta', 'Tarde', 4),
(53, 3, 'Quarta', 'Noite', 4),
(54, 3, 'Quinta', 'Noite', 4),
(55, 3, 'Sexta', 'Tarde', 4),
(56, 3, 'Sexta', 'Noite', 4),
(57, 4, 'Segunda', 'Tarde', 4),
(58, 4, 'Terça', 'Tarde', 4),
(59, 4, 'Quarta', 'Manhã', 4),
(60, 4, 'Quarta', 'Tarde', 4),
(61, 4, 'Quinta', 'Manhã', 4),
(62, 4, 'Quinta', 'Tarde', 4),
(63, 4, 'Sexta', 'Tarde', 6);

-- Dados: docente_unidades_curriculares
INSERT IGNORE INTO `docente_unidades_curriculares` (`docente_id`, `unidade_curricular_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(4, 17),
(4, 18),
(4, 19),
(4, 20),
(4, 21),
(4, 22),
(4, 23),
(4, 24),
(4, 25),
(4, 26),
(4, 27),
(4, 28),
(3, 29),
(3, 30),
(3, 31),
(3, 32),
(3, 33),
(3, 34),
(3, 35),
(3, 36),
(3, 37),
(3, 38),
(3, 39),
(3, 40),
(3, 41),
(3, 42),
(3, 43),
(3, 44);

-- Dados: aprendizagem_quadros
INSERT IGNORE INTO `aprendizagem_quadros` (`id`, `curso_oferta_id`, `unidade_curricular_id`, `sala_id`, `docente_id`, `data_inicio`, `data_fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(1, 5, 24, 1, 4, '2026-05-25', '2026-05-29', 'Ativo', '', '2026-05-28 01:13:25', '2026-05-28 01:13:25');

-- Dados: quadro_horario
INSERT IGNORE INTO `quadro_horario` (`id`, `aprendizagem_quadro_id`, `curso_oferta_id`, `unidade_curricular_id`, `sala_id`, `data_aula`, `hora_inicio`, `hora_fim`, `divisao_por_hora`, `dupla_docencia`, `visita_tecnica`, `ead_assincrona`, `status`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(788, NULL, 4, 29, 3, '2026-05-18', '19:00:00', '22:30:00', 0, 0, 0, 0, 'Ativa', '', '2026-05-27 19:53:14', '2026-05-27 19:53:14'),
(789, 1, 5, 24, 1, '2026-05-25', '13:30:00', '17:30:00', 0, 0, 0, 0, 'Ativa', 'Aceleração', '2026-05-28 01:13:25', '2026-05-28 01:13:25'),
(790, 1, 5, 24, 1, '2026-05-26', '13:30:00', '17:30:00', 0, 0, 0, 0, 'Ativa', 'Aceleração', '2026-05-28 01:13:25', '2026-05-28 01:13:25'),
(791, 1, 5, 24, 1, '2026-05-27', '13:30:00', '17:30:00', 0, 0, 0, 0, 'Ativa', 'Aceleração', '2026-05-28 01:13:25', '2026-05-28 01:13:25'),
(792, 1, 5, 24, 1, '2026-05-28', '13:30:00', '17:30:00', 0, 0, 0, 0, 'Ativa', 'Aceleração', '2026-05-28 01:13:25', '2026-05-28 01:13:25'),
(793, 1, 5, 24, 1, '2026-05-29', '13:30:00', '17:30:00', 0, 0, 0, 0, 'Ativa', 'Aceleração', '2026-05-28 01:13:25', '2026-05-28 01:13:25');

-- Dados: quadro_horario_docentes
INSERT IGNORE INTO `quadro_horario_docentes` (`id`, `quadro_horario_id`, `docente_id`) VALUES
(7, 789, 4),
(8, 790, 4),
(9, 791, 4),
(10, 792, 4),
(11, 793, 4);

-- Dados: calendario_bloqueios
INSERT IGNORE INTO `calendario_bloqueios` (`id`, `data`, `data_fim`, `hora_inicio`, `hora_fim`, `titulo`, `tipo`, `descricao`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, '2026-05-01', NULL, NULL, NULL, 'Dia Mundial do Trabalho', 'Feriado', '', 'Ativo', '2026-05-26 11:42:35', '2026-05-26 17:06:03'),
(2, '2026-05-02', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-26 11:42:50', '2026-05-26 17:05:43'),
(5, '2026-06-04', NULL, NULL, NULL, 'Corpus Christ', 'Feriado', '', 'Ativo', '2026-05-27 19:41:42', '2026-05-28 00:04:21'),
(6, '2026-06-05', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:42:23', '2026-05-28 00:04:21'),
(7, '2026-06-06', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:42:33', '2026-05-28 00:04:21'),
(8, '2026-09-07', NULL, NULL, NULL, 'Independência do Brasil', 'Feriado', '', 'Ativo', '2026-05-27 19:43:32', '2026-05-28 00:04:21'),
(9, '2026-10-12', NULL, NULL, NULL, 'Nossa Senhora Aparecida', 'Feriado', '', 'Ativo', '2026-05-27 19:43:55', '2026-05-28 00:04:21'),
(10, '2026-11-02', NULL, NULL, NULL, 'Finados', 'Feriado', '', 'Ativo', '2026-05-27 19:44:04', '2026-05-28 00:04:21'),
(11, '2026-11-20', NULL, NULL, NULL, 'Dia da Consciência Negra', 'Feriado', '', 'Ativo', '2026-05-27 19:44:40', '2026-05-28 00:04:21'),
(12, '2026-11-21', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:44:51', '2026-05-28 00:04:21'),
(13, '2026-12-02', NULL, NULL, NULL, 'Aniversário de Araçatuba', 'Feriado', '', 'Ativo', '2026-05-27 19:45:20', '2026-05-28 00:04:21'),
(14, '2027-02-09', NULL, NULL, NULL, 'Carnaval', 'Feriado', '', 'Ativo', '2026-05-27 19:47:03', '2026-05-28 00:04:21'),
(15, '2027-02-08', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:47:15', '2026-05-28 00:04:21'),
(16, '2027-03-26', NULL, NULL, NULL, 'Sexta Feira Santa', 'Feriado', '', 'Ativo', '2026-05-27 19:48:16', '2026-05-28 00:04:21'),
(17, '2027-03-27', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:48:23', '2026-05-28 00:04:21'),
(18, '2027-04-21', NULL, NULL, NULL, 'Tiradentes', 'Feriado', '', 'Ativo', '2026-05-27 19:48:32', '2026-05-28 00:04:21'),
(19, '2027-05-01', NULL, NULL, NULL, 'Dia Mundial do Trabalho', 'Feriado', '', 'Ativo', '2026-05-27 19:48:43', '2026-05-28 00:04:21'),
(20, '2027-05-27', NULL, NULL, NULL, 'Corpus Christ', 'Feriado', '', 'Ativo', '2026-05-27 19:48:53', '2026-05-28 00:04:21'),
(21, '2027-05-28', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:49:02', '2026-05-28 00:04:21'),
(22, '2027-09-07', NULL, NULL, NULL, 'Independência do Brasil', 'Feriado', '', 'Ativo', '2026-05-27 19:49:19', '2026-05-28 00:04:21'),
(23, '2027-09-06', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:49:25', '2026-05-28 00:04:21'),
(24, '2027-10-12', NULL, NULL, NULL, 'Nossa Senhora Aparecida', 'Feriado', '', 'Ativo', '2026-05-27 19:51:17', '2026-05-28 00:04:21'),
(25, '2027-10-11', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:51:25', '2026-05-28 00:04:21'),
(26, '2027-11-02', NULL, NULL, NULL, 'Finados', 'Feriado', '', 'Ativo', '2026-05-27 19:51:41', '2026-05-28 00:04:21'),
(27, '2027-11-01', NULL, NULL, NULL, 'Ponte de Feriado', 'Feriado', '', 'Ativo', '2026-05-27 19:51:48', '2026-05-28 00:04:21'),
(28, '2027-11-15', NULL, NULL, NULL, 'Independência do Brasil', 'Feriado', '', 'Ativo', '2026-05-27 19:52:04', '2026-05-28 00:04:21'),
(29, '2027-12-02', NULL, NULL, NULL, 'Aniversário de Araçatuba', 'Feriado', '', 'Ativo', '2026-05-27 19:52:20', '2026-05-28 00:04:21'),
(30, '2026-06-08', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:41:47', '2026-05-28 00:42:28'),
(31, '2026-05-08', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:42:08', '2026-05-28 00:42:08'),
(32, '2026-08-03', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:42:55', '2026-05-28 00:42:55'),
(33, '2026-09-07', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:43:14', '2026-05-28 00:43:14'),
(34, '2026-10-05', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:43:33', '2026-05-28 00:43:33'),
(35, '2026-11-02', NULL, NULL, NULL, 'Parada Pedagógica', 'Parada Pedagogica', '', 'Ativo', '2026-05-28 00:43:47', '2026-05-28 00:43:47'),
(36, '2026-07-13', '2026-07-24', NULL, NULL, 'Recesso Escolar', 'Recesso', '', 'Ativo', '2026-05-28 00:44:55', '2026-05-28 00:45:44'),
(37, '2026-12-07', '2026-12-18', NULL, NULL, 'Recesso Escolar', 'Recesso', '', 'Ativo', '2026-05-28 00:45:34', '2026-05-28 00:45:34');

-- Dados: educacao_corporativa_docentes
INSERT IGNORE INTO `educacao_corporativa_docentes` (`id`, `docente_id`, `data`, `titulo`, `descricao`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 2, '2026-05-26', 'Educor: Mentoria com o mestre', '', 'Ativo', '2026-05-26 17:14:10', '2026-05-26 17:14:10');

-- Dados: sala_trocas
INSERT IGNORE INTO `sala_trocas` (`id`, `quadro_horario_id`, `sala_origem_id`, `sala_destino_id`, `motivo`, `usuario_id`, `criado_em`) VALUES
(1, 39, 1, 2, 'Troca de sala', 1, '2026-05-26 17:31:12');

-- Dados: sistema_logs
INSERT IGNORE INTO `sistema_logs` (`id`, `usuario_id`, `usuario_nome`, `usuario_email`, `nivel_acesso`, `metodo`, `pagina`, `acao`, `descricao`, `dados`, `ip`, `navegador`, `criado_em`) VALUES
(1, NULL, NULL, NULL, NULL, 'POST', 'login', 'autenticar', 'Autenticacao em login', '{\"email\":\"vitrineata@vitrineata.com\",\"senha\":\"[protegido]\"}', '143.0.71.185', 'Mozilla/5.0 (Windows NT 10.0;

INSERT IGNORE INTO `sistema_logs` (`id`, `usuario_id`, `usuario_nome`, `usuario_email`, `nivel_acesso`, `metodo`, `pagina`, `acao`, `descricao`, `dados`, `ip`, `navegador`, `criado_em`) VALUES
(111, 1, 'Salomão', 'vitrineata@vitrineata.com.br', 'Admin', 'POST', 'calendario', 'salvar', 'Cadastro em calendario', '{\"data\":\"2026-06-06\",\"titulo\":\"Ponte de Feriado\",\"tipo\":\"Evento\",\"status\":\"Ativo\",\"descricao\":\"\"}', '186.214.199.151', 'Mozilla/5.0 (Windows NT 10.0;

SET FOREIGN_KEY_CHECKS = 1;
