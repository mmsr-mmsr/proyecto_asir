<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
		if (empty($_POST['campo_email'])) echo "Se ha producido un error al intentar modificar el usuario. Puede que ya no exista, prueba a actualizar la página";
		elseif (empty($_POST['campo_tipo']) or ($_POST['campo_tipo'] != "administrador" and $_POST['campo_tipo'] != "editor") and $_POST['campo_tipo'] != "estandar") echo "El campo tipo debe tener los valores: administrador, editor o estándar.";
		else {
			$resultado_modificacion = modificar_usuario($_POST['campo_email'], $_POST['campo_nombre'], $_POST['campo_tipo']);
			if ($resultado_modificacion !== False) echo "CORRECTO";
			else echo "Ha fallado la modificación del usuario ".$_POST['campo_email'];
		}
  } else header('Location: /PHP/index.php');
?>
