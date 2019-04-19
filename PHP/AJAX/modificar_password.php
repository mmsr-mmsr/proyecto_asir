<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN modificar_usuario (funciones.php) QUE MODIFICA DATOS DE UN USUARIO DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT confirmar_modificar_usuario
		PARÁMETROS:
			- $_POST['campo_email']: EMAIL DEL USUARIO. NO NULL
			- $_POST['campo_nombre']: NOMBRE DEL USUARIO. NULL
			- $_POST['campo_tipo']: EMAIL DEL USUARIO. NO NULL
	*/
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
		$resultado_modificacion_password = modificar_password($_POST['campo_email'], $_POST['campo_password']);
		if ($resultado_modificacion_password === True) echo "CORRECTO";
		elseif ($resultado_modificacion_password === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar la modificación. Pruebe a intentarlo de nuevo.";
		elseif ($resultado_modificacion_password === "NO MODIFICADO") echo "No existe el usuario que se trata de modificar o no se ha cambiado la contraseña. Recarga la página para actualizar los datos.";
		elseif ($resultado_modificacion_password === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
  } else header('Location: /PHP/index.php');
?>
