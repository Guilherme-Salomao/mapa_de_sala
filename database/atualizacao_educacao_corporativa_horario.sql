SET NAMES utf8mb4;

ALTER TABLE educacao_corporativa_docentes
  ADD COLUMN dia_inteiro TINYINT(1) NOT NULL DEFAULT 1 AFTER data,
  ADD COLUMN hora_inicio TIME NULL AFTER dia_inteiro,
  ADD COLUMN hora_fim TIME NULL AFTER hora_inicio;

UPDATE educacao_corporativa_docentes
SET dia_inteiro = 1,
    hora_inicio = NULL,
    hora_fim = NULL;

