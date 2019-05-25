<?php
  session_start();
  include "funciones.php";
  echo exportar_csv_tabla_ubicaciones();
   // echo exportar_csv_tabla_logs();
?>
