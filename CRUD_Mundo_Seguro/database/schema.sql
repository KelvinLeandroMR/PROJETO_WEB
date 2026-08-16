CREATE DATABASE IF NOT EXISTS bd_mundo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bd_mundo;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS governantes;
DROP TABLE IF EXISTS cidades;
DROP TABLE IF EXISTS paises;
DROP TABLE IF EXISTS continentes;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    username VARCHAR(50) PRIMARY KEY,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(80) NOT NULL,
    status CHAR(1) NOT NULL DEFAULT 'A',
    tipo CHAR(1) NOT NULL DEFAULT 'U',
    dt_acesso DATETIME NULL,
    tentativas_falhas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    CONSTRAINT chk_usuarios_status CHECK (status IN ('A','I','B')),
    CONSTRAINT chk_usuarios_tipo CHECK (tipo IN ('A','U'))
) ENGINE=InnoDB;

CREATE TABLE logs (
    id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_acesso DATETIME NOT NULL,
    descricao VARCHAR(800) NULL,
    username VARCHAR(50) NULL,
    CONSTRAINT fk_logs_usuario FOREIGN KEY (username) REFERENCES usuarios(username) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_logs_usuario_data (username, data_acesso)
) ENGINE=InnoDB;

CREATE TABLE continentes (
    id_continente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(18,2) NULL,
    total_paises INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uk_continente_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE paises (
    id_pais INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    continente_id INT UNSIGNED NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(18,2) NULL,
    idioma VARCHAR(100) NULL,
    clima VARCHAR(120) NULL,
    regime_politico VARCHAR(120) NULL,
    moeda VARCHAR(80) NULL,
    UNIQUE KEY uk_pais_continente_nome (continente_id, nome),
    CONSTRAINT fk_pais_continente FOREIGN KEY (continente_id) REFERENCES continentes(id_continente) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_paises_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE cidades (
    id_cidade INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    pais_id INT UNSIGNED NOT NULL,
    populacao BIGINT UNSIGNED NOT NULL DEFAULT 0,
    area_km2 DECIMAL(18,2) NULL,
    clima VARCHAR(120) NULL,
    data_fundacao DATE NULL,
    UNIQUE KEY uk_cidade_pais_nome (pais_id, nome),
    CONSTRAINT fk_cidade_pais FOREIGN KEY (pais_id) REFERENCES paises(id_pais) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_cidades_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE governantes (
    id_governante INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    partido_politico VARCHAR(120) NULL,
    data_nascimento DATE NOT NULL,
    idade TINYINT UNSIGNED NOT NULL,
    data_inicio_mandato DATE NULL,
    data_fim_mandato DATE NULL,
    pais_id INT UNSIGNED NULL,
    cidade_id INT UNSIGNED NULL,
    CONSTRAINT chk_governante_vinculo CHECK ((pais_id IS NOT NULL AND cidade_id IS NULL) OR (pais_id IS NULL AND cidade_id IS NOT NULL)),
    CONSTRAINT fk_governante_pais FOREIGN KEY (pais_id) REFERENCES paises(id_pais) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_governante_cidade FOREIGN KEY (cidade_id) REFERENCES cidades(id_cidade) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_governante_nome (nome)
) ENGINE=InnoDB;

DELIMITER $$
CREATE TRIGGER trg_pais_insert AFTER INSERT ON paises
FOR EACH ROW
BEGIN
    UPDATE continentes SET total_paises = total_paises + 1 WHERE id_continente = NEW.continente_id;
END$$

CREATE TRIGGER trg_pais_delete AFTER DELETE ON paises
FOR EACH ROW
BEGIN
    UPDATE continentes SET total_paises = GREATEST(total_paises - 1, 0) WHERE id_continente = OLD.continente_id;
END$$

CREATE TRIGGER trg_pais_update AFTER UPDATE ON paises
FOR EACH ROW
BEGIN
    IF OLD.continente_id <> NEW.continente_id THEN
        UPDATE continentes SET total_paises = GREATEST(total_paises - 1, 0) WHERE id_continente = OLD.continente_id;
        UPDATE continentes SET total_paises = total_paises + 1 WHERE id_continente = NEW.continente_id;
    END IF;
END$$
DELIMITER ;

-- Conta inicial para o primeiro acesso.
-- Senha: Admin@123 (troque após entrar no sistema).
INSERT INTO usuarios(username, senha, nome, status, tipo)
VALUES ('admin', '$2y$12$6V4YOJdjlcEm0hUtzApaNOT4Ddd2OkYNTjDXK1XHtz6OgD3R2PIJy', 'Administrador', 'A', 'A');

INSERT INTO continentes(nome, populacao, area_km2) VALUES
('América do Sul', 0, 17840000),
('Europa', 0, 10180000),
('Ásia', 0, 44579000),
('África', 0, 30370000);
