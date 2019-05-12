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
		$resultado_articulos_ubicacion = ver_articulos_no_inventariados_por_ubicacion($_POST['campo_codigo']);
	  if ($resultado_articulos_ubicacion === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más tarde.";
		elseif ($resultado_articulos_ubicacion === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
		elseif ($resultado_articulos_ubicacion === "FALLO UBICACION") echo "La ubicación que ha tratado de listar no está disponible o no existe. Actualiza la página para recargar los datos.";
		elseif ($resultado_articulos_ubicacion === "NO ARTICULOS") {
			$resultado = "<option selected>Selecciona una ubicación para añadir</option>";
			echo $resultado;
		}
		elseif (is_array($resultado_articulos_ubicacion)) {
			$resultado = "<option selected>Selecciona una ubicación para añadir</option>";
			foreach ($resultado_articulos_ubicacion as $articulo) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
				$resultado .= "<option id='".$articulo['codigo']."' value='".$articulo['codigo']."'>".$articulo['descripcion']."</option>";
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
