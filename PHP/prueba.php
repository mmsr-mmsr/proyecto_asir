<?php
  session_start();
  include "funciones.php";
  //echo importar_csv_articulos();
  // if (isset($_FILES['fichero'])) {
  //   echo "<pre>";
  //   print_r($_FILES);
  //   print_r (importar_csv_articulos($_FILES['fichero']['tmp_name']));
  // }

  $arreglo[0][0] = "AULA";
  $arreglo[0][1] = "PERRO";
  $arreglo[0][2] = "GATO";
  $arreglo_final[0] = $arreglo[0];
  echo "<pre>";
  print_r($arreglo_final);
?>
<form action="" method="POST" enctype="multipart/form-data">
  <input type="file" name="fichero"/>
  <input type="submit"/>
</form>
