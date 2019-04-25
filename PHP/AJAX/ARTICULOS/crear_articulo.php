<?php
	/*
		DESCRIPCIÓN: SE UTILIZA PARA LLAMAR A LA FUNCIÓN crear_articulo (funciones.php) QUE CREA UN ARTÍCULO EN LA BD DESDE UN FORM Y DEVOLVERLE EL RESULTADO AL NAVEGADOR A TRAVÉS DE AJAX.
		RESULTADO: DEVUELVE HTML EN FUNCIÓN DEL RESULTADO DE LA CONSULTA EN LA BD
		LLAMADA: ES LLAMADA DESDE LA FUNCIÓN DE JAVASCRIPT confirmar_crear_articulo
		PARÁMETROS:
			- $_POST['campo_codigo']: CÓDIGO DEL ARTÍCULO. ES LA CLAVE PRIMARIA DE LA BD. NO PUEDE SER NULL
			- $_POST['campo_descripcion']: DESCRIPCIÓN DEL ARTÍCULO. NO PUEDE SER NULL
			- $_POST['campo_observaciones']: OBSERVACIONES DEL ARTÍCULO. PUEDE SER NULL
	*/
	session_start();
	include "../../funciones.php";
  if (isset($_SESSION['email']) and isset($_SESSION['password']) and isset($_SESSION['tipo']) and $_SESSION['tipo'] == "administrador") { // COMPROBAR QUE SE HAYA INICIADO SESIÓN Y EL USUARIO SEA ADMINISTRADOR
	  $resultado_creacion = crear_articulo($_POST['campo_codigo'], $_POST['campo_descripcion'], $_POST['campo_observaciones']);
    if ($resultado_creacion === True) echo "CORRECTO";
		elseif ($resultado_creacion === "ERROR EN LA BD") echo "Se ha producido un error al conectar con la BD. Compruebe que el servicio está funcionando correcamente.";
    elseif ($resultado_creacion === "FALLO CODIGO") echo "El código debe ser rellenado y tener un formato válido.";
		elseif ($resultado_creacion === "FALLO DESCRIPCION") echo "La descripción debe rellenarse.";
		elseif ($resultado_creacion === "FALLO CONSULTA") echo "Se ha producido un error al insertar la ubicación. Prueba a actualizar la página e inténtalo de nuevo.";
		elseif ($resultado_creacion === "FALLO CREAR") echo "Se ha producido un error al crear la ubicación. Puede que ya exista la ubicación, prueba a actualizar la página.";
  } else header('Location: /PHP/index.php');
?>
