<?php
	session_start();
	include "funciones.php";
	// echo "<pre>";
	// print_r($_POST);
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
} else header('Location: /PHP/index.php');
	/* *** FIN DE BLOQUE *** */

	// ACCIÓN -> EXPORTAR
	if ($_POST['campo_accion'] === "exportar") {
		// TIPO DE FICHERO -> CSV
		if ($_POST['campo_tipo_fichero'] === "csv") {
			// TIPO DE DATO -> CONSULTA
			if ($_POST['campo_tipo_dato'] === "consulta") {
				exportar_csv_consulta($_POST['campo_consulta']);
			// TIPO DE DATO -> CONSULTA ALMACENADA
			} elseif ($_POST['campo_tipo_dato'] === "consultas_almacenadas") {
				exportar_csv_consulta($_POST['campo_consultas_almacenadas']);
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
				// TABLA -> CONSULTAS
				} elseif ($_POST['campo_tabla'] === "consultas") {
					exportar_csv_tabla_consultas();
				// TABLA -> LOGS
				} elseif ($_POST['campo_tabla'] === "logs") {
					exportar_csv_tabla_logs();
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
			// TIPO DE DATO -> CONSULTA
			if ($_POST['campo_tipo_dato'] === "consulta") {
				exportar_excel_consulta($_POST['campo_consulta']);
			// TIPO DE DATO -> CONSULTA ALMACENADA
			} elseif ($_POST['campo_tipo_dato'] === "consultas_almacenadas") {
				exportar_excel_consulta($_POST['campo_consultas_almacenadas']);
			// TIPO DE DATO -> TABLA
			} elseif ($_POST['campo_tipo_dato'] === "tabla") {
				// TABLA -> ARTICULOS
				if ($_POST['campo_tabla'] === "articulos") {
					exportar_excel_tabla_articulos();
				// TABLA -> UBICACIONES
				} elseif ($_POST['campo_tabla'] === "ubicaciones") {
					exportar_excel_tabla_ubicaciones();
				// TABLA -> USUARIOS
				} elseif ($_POST['campo_tabla'] === "usuarios") {
					exportar_excel_tabla_usuarios();
				// TABLA -> STOCK
				} elseif ($_POST['campo_tabla'] === "stock") {
					exportar_excel_tabla_stock();
				// TABLA -> GESTIONA
				} elseif ($_POST['campo_tabla'] === "gestiona") {
					exportar_excel_tabla_gestiona();
				// TABLA -> CONSULTAS
				} elseif ($_POST['campo_tabla'] === "consultas") {
					exportar_excel_tabla_consultas();
				// TABLA -> LOGS
				} elseif ($_POST['campo_tabla'] === "logs") {
					exportar_excel_tabla_logs();
				} else {
					die("TABLA SELECCIONADA INVALIDA");
				}
			// TIPO DE DATO -> ARTÍCULOS DE UNA UBICACIÓN
			} elseif ($_POST['campo_tipo_dato'] === "contenido") {
				exportar_excel_contenido_ubicacion($_POST['campo_ubicacion']);
			// TIPO DE DATO -> UBICACIONES DONDE SE ENCUENTRA UN ARTÍCULO
			} elseif ($_POST['campo_tipo_dato'] === "ubicaciones") {
				exportar_excel_ubicaciones($_POST['campo_articulo']);
			} else {
				die("TIPO DE DATO INVALIDO");
			}
		} else {
			die("TIPO DE FICHERO INVALIDO");
		}
	} elseif ($_POST['campo_accion'] === "importar") {
		if ($_POST['campo_tipo_fichero'] === "csv") {
			if ($_POST['campo_tipo_dato'] === "articulos") {
				print_r(importar_csv_tabla_articulos($_FILES['campo_fichero']['tmp_name']));
			} elseif ($_POST['campo_tipo_dato'] === "ubicaciones") {
				print_r(importar_csv_tabla_ubicaciones($_FILES['campo_fichero']['tmp_name']));
				//echo strlen($resultado[0]);
			} elseif ($_POST['campo_tipo_dato'] === "usuarios") {
				print_r(importar_csv_tabla_usuarios($_FILES['campo_fichero']['tmp_name']));
			} elseif ($_POST['campo_tipo_dato'] === "stock") {
				print_r(importar_csv_tabla_stock($_FILES['campo_fichero']['tmp_name']));
			} elseif ($_POST['campo_tipo_dato'] === "gestiona") {
				echo "<pre>";
				print_r(importar_csv_tabla_gestiona($_FILES['campo_fichero']['tmp_name']));
			} elseif ($_POST['campo_tipo_dato'] === "logs") {
				print_r(importar_csv_tabla_articulos($_FILES['campo_fichero']['tmp_name']));
			} elseif ($_POST['campo_tipo_dato'] === "consultas") {
				print_r(importar_csv_tabla_articulos($_FILES['campo_fichero']['tmp_name']));
			}
		}
	} else {
		die("TIPO DE ACCIÓN INVALIDA");
	}
?>
