<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
      $resultado_borrado = borrar_usuario($_POST['campo_email']);
      if ($resultado_borrado === True) echo "Se ha eliminado correctamente al usuario ".$_POST['campo_email'];
      else echo "No se ha podido eliminar al usuario ".$_POST['campo_email'];
  } else header('Location: /PHP/index.php');
?>