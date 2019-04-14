<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
			//VALIDAR EMAIL
			if (empty($_POST['campo_email'])) echo "FALLO";
			else {
				$resultado_modificacion = modificar_ubicaciones_administrador($_POST['campo_email'], $_POST['campo_ubicaciones']);
				if ($resultado_modificacion === True) echo "CORRECTO";
				else echo "FALLO";
			}
  } else header('Location: /PHP/index.php');
?>
