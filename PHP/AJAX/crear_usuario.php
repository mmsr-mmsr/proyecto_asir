<?php
	session_start();
	include "../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") {
      $resultado_creacion = crear_usuario($_POST['campo_correo'], $_POST['campo_password'], $_POST['campo_nombre'], $_POST['campo_tipo']);
      if ($resultado_creacion === True) echo "Se ha creado correctamente al usuario ".$_POST['campo_correo'];
      else echo "No se ha podido crear al usuario ".$_POST['campo_correo'];
  } else header('Location: /PHP/index.php');
?>
