use mapa_de_sala;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('Admin', 'Gestor', 'Professor', 'Apoio') NOT NULL DEFAULT 'Professor',
    status ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo',
    ultimo_login DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);