<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_articulos (funciones.php) QUE CARGA LOS ARTÍCULOS DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT recargar_articulos
		PARÁMETROS:
			- $_POST['campo_filtro']: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LOS ARTÍCULOS QUE COINCIDAN CON EL CÓDIGO O DESCRIPCIÓN DADO POR EL USUARIO
	*/
	session_start();
	include "../../funciones.php";
  	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo'])) { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
		$resultado_articulos = ver_articulos($_POST['campo_filtro']);
	  if ($resultado_articulos === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más rápido.";
		elseif ($resultado_articulos === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
		elseif ($resultado_articulos === "NO ARTICULOS") echo "No se ha encontrado a ningún artículo que concuerde con el patrón de búsqueda.";
		//SI NO SE HA PRODUCIDO NINGÚN ERROR, RECORREMOS EL ARRAY RESULTADO
		elseif ($resultado_articulos) {
			$resultado = "";
			foreach ($resultado_articulos as $ubicacion) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
				$resultado .= "
				<tr>
					<td><input type='text' name='campo_codigo' value='".$ubicacion['codigo']."' readonly></td>
					<td><input type='text' name='campo_descripcion' value='".$ubicacion['descripcion']."' readonly></td>
					<td><input type='text' name='campo_observaciones' value='".$ubicacion['observaciones']."' readonly></td>
					<td>
						<button onclick='eliminar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar artículo'><i class='fas fa-trash'></i></button>
						<button onclick='modificar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar artículo'><i class='fas fa-pen'></i></button>
					</td>
				</tr>
				";
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
