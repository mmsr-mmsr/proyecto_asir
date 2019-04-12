<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
			//VALIDAR EMAIL, PASSWORD Y TIPO
			if (empty($_POST['campo_email'])) echo "Se debe rellenar el campo email.";
			elseif (!filter_var($_POST['campo_email'], FILTER_VALIDATE_EMAIL)) echo "El email debe tener un formato correcto.";
			elseif (empty($_POST['campo_password'])) echo "Se debe rellenar el campo contraseña.";
			elseif (empty($_POST['campo_tipo']) or ($_POST['campo_tipo'] != "administrador" and $_POST['campo_tipo'] != "editor") and $_POST['campo_tipo'] != "estandar") echo "El campo tipo debe tener los valores: administrador, editor o estándar.";
			else {
	      $resultado_creacion = crear_usuario($_POST['campo_email'], $_POST['campo_password'], $_POST['campo_nombre'], $_POST['campo_tipo']);
	      if ($resultado_creacion === True) echo "CORRECTO";
	      else echo "No se ha podido crear al usuario ".$_POST['campo_email'];
			}
  } else header('Location: /PHP/index.php');
?>
