DROP DATABASE inventario;
SET autocommit=0;
CREATE DATABASE IF NOT EXISTS inventario;
USE inventario;

CREATE TABLE IF NOT EXISTS ubicaciones (
	codigo VARCHAR(4),
	descripcion VARCHAR(100) NOT NULL,
	observaciones VARCHAR(100),
	CONSTRAINT ubicaciones_pk PRIMARY KEY (codigo)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS articulos (
	codigo VARCHAR(4),
	descripcion VARCHAR(100) NOT NULL,
	observaciones VARCHAR(100),
	CONSTRAINT articulos_pk PRIMARY KEY (codigo)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS stock (
	ubicacion VARCHAR(4),
	articulo VARCHAR(4),
	cantidad INT NOT NULL,
	CONSTRAINT stock_pk PRIMARY KEY (ubicacion, articulo),
	CONSTRAINT stock_ubicacion_fk FOREIGN KEY (ubicacion) REFERENCES ubicaciones (codigo) ON DELETE CASCADE,
	CONSTRAINT stock_articulo_fk FOREIGN KEY (articulo) REFERENCES articulos (codigo) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
	email VARCHAR(30),
	password VARCHAR(100) NOT NULL,
	tipo VARCHAR(13) NOT NULL,
	CONSTRAINT usuarios_pk PRIMARY KEY (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS GESTIONA (
	ubicacion VARCHAR(4),
	usuario VARCHAR(30),
	CONSTRAINT gestiona_pk PRIMARY KEY (ubicacion, usuario),
	CONSTRAINT gestiona_ubicacion_fk FOREIGN KEY (ubicacion) REFERENCES ubicaciones (codigo) ON DELETE CASCADE,
	CONSTRAINT gestiona_usuario_fk FOREIGN KEY (usuario) REFERENCES usuarios (email) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS logs (
	fecha INT,
	usuario VARCHAR(30),
	descripcion VARCHAR(100) NOT NULL,
	tipo VARCHAR(5) NOT NULL,
	CONSTRAINT logs_pk PRIMARY KEY (fecha, usuario),
	CONSTRAINT logs_usuario_fk FOREIGN KEY (usuario) REFERENCES usuarios (email) ON DELETE CASCADE
) ENGINE = InnoDB;

-- EMULAR CONDICION CHECK SQL MEDIANTE TRIGGERS YA QUE MYSQL NO LOS PERMITE. SI LAS CONDICIONES NO SE CUMPLE GENERA UNA EXCEPCION
-- EL PRIMER PARAMETRO HACE REFERENCIA CON QUE TABLA VA A TRABAJAR.
-- EL SEGUNDO PARAMETRO HACE REFERENCIA AL NUEVO VALOR QUE SE VA A INSERTAR/MODIFICAR
DELIMITER $
CREATE OR REPLACE PROCEDURE comprobar_tipos (IN tabla VARCHAR(13), IN tipo VARCHAR(13))
BEGIN
	-- SI LA COLUMNA TIPO DE LA TABLA LOGS NO ES ERROR NI LOGIN, LANZAMOS ERROR
	IF tabla = "logs" THEN
		IF tipo NOT IN ("error", "login") THEN
			SIGNAL SQLSTATE "45000"
				SET MESSAGE_TEXT = "EL TIPO DE LOG NO ES CORRECTO";
		END IF;
	-- SI LA COLUMNA TIPO DE LA TABLA USUARIOSNO ES ADMINISTRADOR, EDITOR NI ESTANDAR, LANZAMOS ERROR
	ELSEIF tabla = "usuarios" THEN
		IF tipo NOT IN ("administrador", "editor", "estandar") THEN
			SIGNAL SQLSTATE "45000"
				SET MESSAGE_TEXT = "EL TIPO DE USUARIO NO ES CORRECTO";
		END IF;
	END IF;
	
END$

-- TRIGGER PARA INSERTAR UN USUARIO, LLAMA AL PROCEDIMIENTO ANTERIOR
CREATE OR REPLACE TRIGGER comprobar_tipo_usuario_insert BEFORE INSERT ON usuarios
FOR EACH ROW
BEGIN
    CALL comprobar_tipos("usuarios", new.tipo);
END$

-- TRIGGER PARA MODIFICAR UN USUARIO, LLAMA AL PROCEDIMIENTO ANTERIOR
CREATE OR REPLACE TRIGGER comprobar_tipo_usuario_update BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
    CALL comprobar_tipos("usuarios", new.tipo);
END$

-- TRIGGER PARA INSERTAR UN LOG, LLAMA AL PROCEDIMIENTO ANTERIOR
CREATE OR REPLACE TRIGGER comprobar_tipo_log_insert BEFORE INSERT ON logs
FOR EACH ROW
BEGIN
    CALL comprobar_tipos("logs", new.tipo);
END$

DELIMITER ;

INSERT INTO ubicaciones VALUES ("1234AAA", "SECRETARIA", NULL);
INSERT INTO articulos VALUES ("1234BBB", "LAPIZ", NULL);
INSERT INTO usuarios VALUES ("1234CCC", "abc", "administrador");
INSERT INTO stock VALUES ("1234AAA", "1234BBB", 1);
