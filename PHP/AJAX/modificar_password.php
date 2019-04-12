<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
		if (empty($_POST['campo_email'])) echo "Se ha producido un error al intentar modificar la contraseña. Puede que ya no exista el usuario, prueba a actualizar la página";
		elseif (empty($_POST['campo_password'])) echo "El campo contraseña no puede estar vacío.";
		else {
			$resultado_modificacion_password = modificar_password($_POST['campo_email'], $_POST['campo_password']);
			if ($resultado_modificacion_password !== False) {
				enviar_email($_POST['campo_email'], "Cambio de contraseña", "Su nueva contraseña es: ".$_POST['campo_password']);
				echo "CORRECTO";
			}
			else echo "No se ha podido cambiar la contraseña al usuario ".$_POST['campo_email'];
		}
  } else header('Location: /PHP/index.php');
?>
