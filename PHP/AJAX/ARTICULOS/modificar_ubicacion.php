<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN modificar_ubicacion (funciones.php) QUE MODIFICA DATOS DE UNA UBICACIÓN DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT confirmar_modificar_ubicacion
		PARÁMETROS:
			- $_POST['campo_codigo']: CÓDIGO DE LA UBICACIÓN. NO NULL
			- $_POST['campo_descripcion']: DESCRIPCIÓN DE LA UBICACIÓN. NO NULL
			- $_POST['campo_observaciones']: OBSERVACIONES DE LA UBICACIÓN. NULL
	*/
	session_start();
	include "../../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE SE HAYA INICIADO SESIÓN Y SEA ADMIN
		$resultado_modificacion = modificar_ubicacion($_POST['campo_codigo'], $_POST['campo_descripcion'], $_POST['campo_observaciones']);
		if ($resultado_modificacion === True) echo "CORRECTO";
		elseif ($resultado_modificacion === "FALLO CODIGO") echo "Se debe modificar una descripción con un código correcto. Prueba a actualizar la página e intentarlo de nuevo.";
		elseif ($resultado_modificacion === "FALLO DESCRIPCION") echo "Se debe rellenar el campo descripción. Prueba a actualizar la página e intentarlo de nuevo.";
		elseif ($resultado_modificacion === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar la modificación. Prueba a intentarlo de nuevo.";
		elseif ($resultado_modificacion === "NO MODIFICADO") echo "No existe la ubicación que se trata de modificar o no se han cambiado datos. Recarga la página para actualizar los datos.";
		elseif ($resultado_modificacion === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
  } else header('Location: /PHP/index.php');
?>
