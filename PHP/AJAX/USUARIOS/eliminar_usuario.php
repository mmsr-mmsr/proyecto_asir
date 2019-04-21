<?php
	/*
		DESCRIPCIÓN: SE UTILIZAR PARA LLAMAR A LA FUNCIÓN borrar_usuario (funciones.php) QUE CREA ELIMINA UN USUARIO DE LA BD Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT confirmar_crear_usuario
		PARÁMETROS:
			- $_POST['campo_email']: EMAIL DEL USUARIO.
	*/
	session_start();
	include "../../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE EL USUARIO SEA ADMIN
		  $resultado_borrado = borrar_usuario($_POST['campo_email']);
      if ($resultado_borrado === True) echo "CORRECTO";
			elseif ($resultado_borrado === "FALLO CONSULTA") echo "Se ha producido un error al ejecutar el borrado. Pruebe a intentarlo de nuevo.";
      elseif ($resultado_borrado === "NO ELIMINADO") echo "No existe el usuario que se trata de eliminar. Recarge la página para actualizar los datos.";
			elseif ($resultado_borrado === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
  } else header('Location: /PHP/index.php'); //SI EL USUARIO NO ES ADMINISTRADOR SE LE REDIRIGE AL INICIO
?>
