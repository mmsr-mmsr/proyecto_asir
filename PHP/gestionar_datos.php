<?php
	session_start();
	include "funciones.php";
	/* *** BLOQUE DE CÓDIGO DE CONTROL SOBRE LA SESIÓN. IGUAL AL DE LOGS YA QUE SOLO USUARIOS ADMIN PODRÁN ACCEDER. DIFIERE DEL RESTO. *** */
	/* 1- SE COMPRUEBA SI HAY UNA SESIÓN INICIADA. EN CASO AFIRMATIVO SE COMPRUEBA EL TIPO DE USUARIO. SI NO ES ADMINISTRADOR SE REDIRIGE A UBICACIONES.PHP*/
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) {
		if ($_SESSION['tipo'] !== "administrador") header('Location: /PHP/ubicaciones.php');
	}

	/* 2- SE COMPRUEBA SI HAY COOKIES ALMACENADAS CON CREDENCIALES, EN TAL CASO SE VALIDAN EN LA DATABASE. SI SON ERRÓNEAS SE ELIMINAN Y SE REDIRIGEN A index.php*/
	elseif (isset($_COOKIE['email']) and isset($_COOKIE['password'])) {
		$resultado_validacion = validar_login($_COOKIE['email'], $_COOKIE['password']);
		if ($resultado_validacion === True)	{
			if ($_SESSION['tipo'] !== "administrador") header('Location: /PHP/ubicaciones.php'); // SI EL USUARIO NO ES ADMINISTRADOR SE LE REDIRIGE
		} else {
			setcookie("email", "", time() - 1, "/");
			setcookie("password", "", time() - 1, "/");
			header('Location: /PHP/index.php');
		}
	/* SI NO HA SE HA VALIDADO MEDIANTE LA SESIÓN NI COOKIES SE LE REDIRIGE */
	} else header('Location: /PHP/ubicaciones.php');
	/* *** FIN DE BLOQUE *** */

	// ACCIÓN -> EXPORTAR
	if ($_POST['campo_accion'] === "exportar") {
		// TIPO DE FICHERO -> CSV
		if ($_POST['campo_tipo_fichero'] === "csv") {
			// TIPO DE DATO -> CONSULTA
			if ($_POST['campo_tipo_dato'] === "consulta") {
				exportar_csv_consulta($_POST['campo_consulta']);
			// TIPO DE DATO -> TABLA
			} elseif ($_POST['campo_tipo_dato'] === "tabla") {
				// TABLA -> ARTICULOS
				if ($_POST['campo_tabla'] === "articulos") {
					exportar_csv_tabla_articulos();
				// TABLA -> UBICACIONES
				} elseif ($_POST['campo_tabla'] === "ubicaciones") {
					exportar_csv_tabla_ubicaciones();
				// TABLA -> USUARIOS
				} elseif ($_POST['campo_tabla'] === "usuarios") {
					exportar_csv_tabla_usuarios();
				// TABLA -> STOCK
				} elseif ($_POST['campo_tabla'] === "stock") {
					exportar_csv_tabla_stock();
				// TABLA -> GESTIONA
				} elseif ($_POST['campo_tabla'] === "gestiona") {
					exportar_csv_tabla_gestiona();
				} else {
					die("TABLA SELECCIONADA INVALIDA");
				}
			// TIPO DE DATO -> ARTÍCULOS DE UNA UBICACIÓN
			} elseif ($_POST['campo_tipo_dato'] === "contenido") {
				exportar_csv_contenido_ubicacion($_POST['campo_ubicacion']);
			// TIPO DE DATO -> UBICACIONES DONDE SE ENCUENTRA UN ARTÍCULO
			} elseif ($_POST['campo_tipo_dato'] === "ubicaciones") {
				exportar_csv_ubicaciones($_POST['campo_articulo']);
			} else {
				die("TIPO DE DATO INVALIDO");
			}
		} elseif ($_POST['campo_tipo_fichero'] === "excel") {

		} else {
			die("TIPO DE FICHERO INVALIDO");
		}
	} elseif ($_POST['campo_accion'] === "importar") {

	} else {
		die("TIPO DE ACCIÓN INVALIDA");
	}
?>
