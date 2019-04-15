<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE EL USUARIO SEA ADMIN
		if (!isset($_POST['campo_email']) or empty($_POST['campo_email'])) echo "FALLO";
		else {
      $resultado_borrado = borrar_usuario($_POST['campo_email']);
      if ($resultado_borrado === True) echo "CORRECTO";
      else echo "FALLO";
		}
  } else header('Location: /PHP/index.php'); //SI EL USUARIO NO ES ADMINISTRADOR SE LE REDIRIGE AL INICIO
?>
