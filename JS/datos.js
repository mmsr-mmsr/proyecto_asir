function mostrar_fichero(accion) {
  if (accion == "exportar") {
    $("#csv_div").show();
    $("#excel_div").show();
    $("#excel_div").attr("onclick", "mostrar_dato('exportar')");
    $("#csv_div").attr("onclick", "mostrar_dato('exportar')");
    $("#campo_enviar").val("Exportar");
  } else if (accion == "importar") {
    $("#csv_div").show();
    $("#excel_div").hide();
    $("#csv_div").attr("onclick", "mostrar_dato('importar')");
    $("#campo_enviar").val("Importar");
  }
  // $("#csv").prop("checked", false);
  // $("#excel").prop("checked", false);
  $("#dato_div").hide();
  $("#boton_div").hide();
}

function mostrar_dato(accion) {
  if (accion == "exportar") {
    // MOSTRAR LAS OPCIONES DE EXPORTACIÓN
    $("#consulta_div").show();
    $("#consultas_almacenadas_div").show();
    $("#tabla_div").show();
    $("#contenido_div").show();
    $("#ubicacion_div").show();

    // OCULTAR LAS OPCIONES DE IMPORTACIÓN
    $("#articulos_div").hide();
    $("#ubicaciones_div").hide();
    $("#usuarios_div").hide();
    $("#gestiona_div").hide();
    $("#stock_div").hide();
    $("#logs_div").hide();
    $("#consultas_div").hide();
  } else if (accion == "importar") {
    // MOSTRAR LAS OPCIONES DE IMPORTACIÓN
    $("#articulos_div").show();
    $("#ubicaciones_div").show();
    $("#usuarios_div").show();
    $("#gestiona_div").show();
    $("#stock_div").show();
    $("#logs_div").show();
    $("#consultas_div").show();
    // OCULTAR LAS OPCIONES DE EXPORTACIÓN
    $("#consulta_div").hide();
    $("#consultas_almacenadas_div").hide();
    $("#tabla_div").hide();
    $("#contenido_div").hide();
    $("#ubicacion_div").hide();
  }
  $("#dato_div").show();
  $("#boton_div").hide();
}

function mostar_boton(campo_fichero = '') {
  $("#boton_div").show();
  if (campo_fichero == "campo_fichero") {
    $("#campo_fichero_div").show();
  }
}

$("#guardar_consulta").click(function() {
  var consulta;
  consulta = $("#campo_consulta").val();
  if (consulta.length == 0) {
    $.alert({
      title: "ERROR",
      content: "No se puede guardar una consulta vacía",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
  } else {
    $.post("../PHP/AJAX/DATOS/crear_consulta.php",
    {
      campo_consulta: consulta
    },
    function(resultado) {
      if (resultado == "CORRECTO") {
        $.alert({
          title: "CONSULTA ALMACENADA",
          content: "Se ha almacenado la consulta",
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        recargar_consultas();
      } else {
        $.alert({
          title: "ERROR",
          content: resultado,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
      }
    });
  }
});

function recargar_consultas() {
  $.post("../PHP/AJAX/DATOS/recargar_consultas.php",
  {
  },
  function(resultado) {
    if (resultado.substr(0,3) == "<op") {
      $("#lista_consultas_almacenadas").html(resultado);
    } else {
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    }
  });
}
