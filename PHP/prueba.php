<?php
  session_start();
  include "funciones.php";
  // echo exportar_csv_tabla_gestiona();
   // echo exportar_csv_tabla_logs();
  if (isset($_FILES['fichero'])) {
    echo "<pre>";
    print_r(importar_csv_tabla_ubicaciones($_FILES['fichero']['tmp_name']));
  }
?>
<form action="" method="POST" enctype="multipart/form-data">
  <input type="file" name="fichero"/>
  <input type="submit"/>
</form>
