<?php
/*
	DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN ver_logs (funciones.php) QUE CARGA LOS LOGS DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
	RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
	LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT recargar_logs
	PARÁMETROS:
		- $_POST['campo_filtro']: PERMITE INDICAR UNA CADENA PARA VISUALIZAR ÚNICAMENTE LOS ARTÍCULOS QUE COINCIDAN CON EL CÓDIGO O DESCRIPCIÓN DADO POR EL USUARIO
*/
	session_start();
	include "../../funciones.php";
  	if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] === "administrador") { // COMPROBAR QUE UN USUARIO HAYA INICIADO SESIÓN
			$resultado_logs = ver_logs($_POST['campo_indice'], $_POST['campo_inicio'], $_POST['campo_fin'], $_POST['campo_usuario'], $_POST['campo_descripcion'], $_POST['campo_tipo']);
		  if ($resultado_logs === "ERROR EN LA BD") echo "Se ha producido un error al conectarse al servidor. Prueba a conectarte más rápido.";
			elseif ($resultado_logs === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
			elseif ($resultado_logs === "NO UBICACIONES") echo "No se ha encontrado a ningún artículo que concuerde con el patrón de búsqueda.";
			//SI NO SE HA PRODUCIDO NINGÚN ERROR, RECORREMOS EL ARRAY RESULTADO
			elseif (is_array($resultado_logs)) {
				$resultado = "";
				foreach ($resultado_logs as $log) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
					$resultado .= "
						<tr>
							<td><input type='text' name='campo_codigo' value='".date("d/m/Y H:i:s", $log['fecha'])."' readonly></td>
							<td><input type='text' name='campo_descripcion' value='".$log['usuario']."' readonly></td>
							<td><input type='text' name='campo_observaciones' value='".$log['descripcion']."' readonly></td>
							<td><input type='text' name='campo_observaciones' value='".$log['tipo']."' readonly></td>
						</tr>
					";
				}
				echo $resultado;
			}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
