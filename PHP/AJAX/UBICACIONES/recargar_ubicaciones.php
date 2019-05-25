<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_ubicaciones (funciones.php) QUE CARGA LAS UBICACIONES DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT recargar_ubicaciones
		PARÁMETROS:
			- $_POST['campo_filtro']: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LAS UBICACIONES QUE COINCIDAN CON EL NOMBRE O UN EMAIL PARA MOSTRAR LAS UBICACIONES QUE PUEDE VISUALIZAR EL USUARIO
	*/
	session_start();
	include "../../funciones.php";
  	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
		$resultado_ubicaciones = ver_ubicaciones($_POST['campo_filtro']);
	  if ($resultado_ubicaciones === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más tarde.";
		elseif ($resultado_ubicaciones === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
		elseif ($resultado_ubicaciones === "NO UBICACIONES") echo "No se ha encontrado a ningún usuario que concuerde con el patrón de búsqueda.";
		elseif ($resultado_ubicaciones === "NO UBICACIONES USUARIO") echo "Actualmente no puedes gestionar ninguna ubicación. Solicítale al administrador permisos sobre las ubicaciones necesarias.";
		//SI NO SE HA PRODUCIDO NINGÚN ERROR, RECORREMOS EL ARRAY RESULTADO
		else {
			$resultado = "";
			foreach ($resultado_ubicaciones as $ubicacion) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
				$resultado .= "<tr>
					<td><input type='text' name='campo_codigo' value='".$ubicacion['codigo']."' readonly></td>
					<td><input type='text' name='campo_descripcion' value='".$ubicacion['descripcion']."' readonly></td>
					<td><input type='text' name='campo_observaciones' value='".$ubicacion['observaciones']."' readonly></td>
					<td>
						<button onclick='ver_articulos(this)' type='button' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></button>
						<button onclick='eliminar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar ubicación'><i class='fas fa-trash'></i></button>
						<button onclick='modificar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar ubicación'><i class='fas fa-pen'></i></button>
					</td>
				</tr>
				";
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
