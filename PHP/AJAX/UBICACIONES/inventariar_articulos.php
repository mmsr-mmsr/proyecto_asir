<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_articulos_por_ubicacion (funciones.php) QUE CARGA LOS ARTÍCULOS DE UNA UBICACIÓN Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT ver_articulos
		PARÁMETROS:
			- $_POST['campo_codigo']: INDICA EL CÓDIGO DE LA UBICACIÓN QUE SE VA A LISTAR
	*/
	session_start();
	include "../../funciones.php";
	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
		if ($_SESSION['tipo'] === "estandar") echo "Únicamente los usuarios administradores y editores pueden inventariar una ubicación. Solicita al administrador permisos para inventariar.";
		else {
			$resultado_validacion = validar_permisos_inventariar($_POST['campo_ubicacion'], $_SESSION['email']);
			if ($resultado_validacion === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más tarde.";
			elseif ($resultado_validacion === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
			elseif ($resultado_validacion === "FALLO CODIGO") echo "La ubicación que has tratado de modificar no está disponible o no existe. Actualiza la página para recargar los datos.";
			elseif ($resultado_validacion === "FALLO PERMISOS") echo "No tienes permisos para modificar esta ubicación. Solicita al administrador permisos para inventariar esta ubicación.";
			elseif ($resultado_validacion === "PERMISOS CORRECTOS") {
				$resultado_inventariado = inventariar_ubicacion($_POST['campo_ubicacion'], $_POST['campo_articulos']);
				if ($resultado_inventariado === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más tarde.";
				elseif ($resultado_inventariado === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
				elseif ($resultado_inventariado === "FALLO CODIGO" or $resultado_inventariado === "FALLO UBICACION") echo "La ubicación que has tratado de modificar no está disponible o no existe. Actualiza la página para recargar los datos.";
				elseif ($resultado_inventariado === "FALLO CANTIDAD") echo "El campo cantidad no se puede dejar vacío, con un valor igual a 0 ni contener 0 a la izquiera. Si el artículo no está en la ubicacion elimínalo haciendo click en la papelera.";
				elseif ($resultado_inventariado === True) echo "CORRECTO";
			}
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
