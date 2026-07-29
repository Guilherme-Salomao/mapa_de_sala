SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS turma_unidades_curriculares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_oferta_id INT NOT NULL,
  unidade_curricular_id INT NOT NULL,
  codigo VARCHAR(20) NOT NULL,
  nome VARCHAR(200) NOT NULL,
  carga_horaria DECIMAL(8,2) NOT NULL,
  status ENUM('Ativa','Inativa') NOT NULL DEFAULT 'Ativa',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_turma_uc (curso_oferta_id, unidade_curricular_id),
  KEY fk_turma_uc_unidade (unidade_curricular_id),
  CONSTRAINT fk_turma_uc_oferta
    FOREIGN KEY (curso_oferta_id) REFERENCES cursos_ofertas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_turma_uc_unidade
    FOREIGN KEY (unidade_curricular_id) REFERENCES unidades_curriculares(id)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cria a matriz inicial das turmas existentes sem substituir matrizes ja registradas.
INSERT IGNORE INTO turma_unidades_curriculares (
  curso_oferta_id,
  unidade_curricular_id,
  codigo,
  nome,
  carga_horaria,
  status
)
SELECT
  co.id,
  uc.id,
  uc.codigo,
  uc.nome,
  uc.carga_horaria,
  uc.status
FROM cursos_ofertas co
INNER JOIN unidades_curriculares uc
  ON uc.curso_modelo_id = co.curso_modelo_id
WHERE uc.status = 'Ativa';

-- IMPORTANTE:
-- O preenchimento acima usa a carga atualmente cadastrada na UC apenas como base inicial.
-- Se uma turma antiga deve manter outra carga, ajuste somente sua matriz, por exemplo:
--
-- UPDATE turma_unidades_curriculares
-- SET carga_horaria = 96
-- WHERE curso_oferta_id = 10
--   AND codigo = 'UC02';
