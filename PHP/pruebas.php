<?php
  session_start();
  include "funciones.php";
	$inserciones[] = "Se ha insertado correctamente la línea AAA";
  $inserciones[] = "Se ha insertado correctamente la línea BBB";
  $inserciones[] = "Se ha insertado correctamente la línea CCC";
  $inserciones[] = "Se ha insertado correctamente la línea DDD";
  $inserciones[] = "Se ha insertado correctamente la línea EEE";
  $fallos[] = "FALLO AL INSERTAR XXX";
  $fallos[] = "FALLO AL INSERTAR ZZZ";
  $inserciones = str_replace("Se ha insertado correctamente la línea", "No se ha podido insertar la siguiente línea debido al fallo en la inserción de alguna línea anterior: ", $inserciones);
  $fallos = array_merge($fallos, $inserciones);
  $inserciones = null;
  echo "<pre>";
  print_r($fallos);
?>
