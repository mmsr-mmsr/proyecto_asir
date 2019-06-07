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
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE SE HAYA INICIADO SESIÓN Y EL USUARIO SEA ADMINISTRADOR
		$resultado_consultas = ver_consultas($_SESSION['email']);
	  if ($resultado_consultas === "ERROR EN LA BD") echo "Se ha producido un error al conectarse a la BD. Comprueba que el servicio está levantado correctamente";
		elseif ($resultado_consultas === "FALLO CONSULTA") echo "Se ha producido un error al consultar los datos. Prueba a actualizar la página y volver a intentarlo.";
		//SI NO SE HA PRODUCIDO NINGÚN ERROR, RECORREMOS EL ARRAY RESULTADO
		elseif (is_array($resultado_consultas)) {
			$resultado = "";
			foreach ($resultado_consultas as $consulta) { // RECORRER EL ARRAY OBTENIDO MOSTRANDO LOS DATOS
				$resultado .= "<option value='".$consulta."'>".$consulta."</option>";
			}
			echo $resultado;
		}
	} else header('Location: /PHP/index.php'); // SI NO ES ADMINISTRADOR LE REDIRIGIMOS A LA PÁGINA PRINCIPAL
?>
