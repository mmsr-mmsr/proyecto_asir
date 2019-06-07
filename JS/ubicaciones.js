if($(window).width() < 768) {
   // $("#myDiv").addClass("myClass");
  $("#tabla_ubicaciones").removeClass("table-striped");
  $("#tabla_ubicaciones").removeClass("table-hover");
  //$("#crear_ubicacion").html("<i class='fas fa-plus-circle fa-lg'></i>");
  crear_ubicacion = $("#crear_ubicacion_li").html();
  $("#crear_ubicacion_li").empty();
  $("#pagina").append(crear_ubicacion);
}
$(window).resize(function() {
  if($(window).width() < 768) {
    $("#tabla_ubicaciones").removeClass("table-striped");
    $("#tabla_ubicaciones").removeClass("table-hover");
    crear_ubicacion = $("#crear_ubicacion_li").html();
    $("#crear_ubicacion_li").empty();
    $("#pagina").append(crear_ubicacion);
    $("#crear_ubicacion").click(function(){
      $("#contenido_ubicaciones").append(
        "<tr>" +
          "<td><input type='text' name='campo_codigo' class=''></td>" +
          "<td><input type='text' name='campo_descripcion'></td>" +
          "<td><input type='text' name='campo_observaciones'></td>" +
          "<td>" +
            "<button onclick='confirmar_crear_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
            "<button onclick='cancelar_nueva_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
          "</td>" +
        "</tr>"
      );
    });
  } else {
    crear_ubicacion = $("#crear_ubicacion");
    $("#crear_ubicacion").remove();
    $("#crear_ubicacion_li").append(crear_ubicacion);

    $("#crear_ubicacion").click(function(){
      $("#contenido_ubicaciones").append(
        "<tr>" +
          "<td><input type='text' name='campo_codigo' class=''></td>" +
          "<td><input type='text' name='campo_descripcion'></td>" +
          "<td><input type='text' name='campo_observaciones'></td>" +
          "<td>" +
            "<button onclick='confirmar_crear_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
            "<button onclick='cancelar_nueva_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
          "</td>" +
        "</tr>"
      );
    });
    $("#tabla_ubicaciones").addClass("table-striped");
    $("#tabla_ubicaciones").addClass("table-hover");
  }
});
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA COMPROBAR SI UN CÓDIGO TIENE EL FORMATO CORRECTO MEDIANTE UNA EXPRESIÓN REGULAR
  RESULTADO: DEVUELVE FALSE SI NO TIENE UN FORMATO CORRECTO O TRUE SI SÍ QUE LO TIENE
  LLAMADA: ES LLAMADA AL CREAR UNA UBICACIÓN.
  PARÁMETROS:
    - CÓDIGO: CADENA QUE VALIDAR
*/
function validor_codigo(codigo) {
  var re = /^[A-Z]{2}\d{2}$/;
  return re.test(String(codigo));
} // ******** COMPLETADO ********
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECARGAR LA TABLA QUE CONTIENE INFORMACIÓN SOBRE LAS UBICACIONES.
  LLAMADA: ES LLAMADA CADA VEZ QUE SE REALIZA UN CAMBIO VISIBLE EN LA TABLA (BORRADO, MODIFICACIÓN O ADICIÓN DE UBICACIONES)
  PARÁMETROS:
    - FILTRO: CADENA POR LA QUE FILTRAR EL RESULTADO, SI NO SE LE PASA TOMA EL VALOR = "ninguno"
*/
function recargar_ubicaciones(filtro = "ninguno"){
  $.post("../PHP/AJAX/UBICACIONES/recargar_ubicaciones.php",
  {
    campo_filtro: filtro
  },
  function(resultado) {
    if (resultado.substring(0,3) == "<tr") {
      $("#cabecera_ubicaciones").html(
        "<tr>" +
  				"<th scope='col'>Código</th>" +
  				"<th scope='col'>Descripción</th>" +
  				"<th scope='col'>Observaciones</th>" +
				  "<th scope='col'>Acciones</th>" +
			   "</tr>"
       );
      $("#contenido_ubicaciones").html(resultado); //EL RESULTADO DEL SCRIPT PHP SUSTITUYE EL CONTENIDO DE LA TABLA
    } else {
      $("#contenido_ubicaciones").html("");
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    }
  });
} // ******** COMPLETADO ********
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA FILTRAR LA TABLA UBICACIONES POR NOMBRE/DESCRIPCIÓN. SI EL CAMPO 'campo_buscar' ESTÁ VACÍO MUESTRA LA TABLA ENTERA
  LLAMADA: ES LLAMADA CADA VEZ QUE SE HACE CLICK EN EL BOTÓN <button onclick='buscar_ubicaciones()'>
  PARÁMETROS:
*/
function buscar_ubicaciones() {
  var texto;
  texto = $("#campo_buscar").val();
  if (texto.length > 0) {
    recargar_ubicaciones(texto);
  } else {
    recargar_ubicaciones();
  }
}
// AL HACER CLICK EN EL BOTÓN DE CREAR UBICACIÓN SE AÑADE UNA FILA A LA TABLA CON UN FORM
$("#crear_ubicacion").click(function(){
  $("#contenido_ubicaciones").append(
    "<tr>" +
      "<td><input type='text' name='campo_codigo' class=''></td>" +
      "<td><input type='text' name='campo_descripcion'></td>" +
      "<td><input type='text' name='campo_observaciones'></td>" +
      "<td>" +
        "<button onclick='confirmar_crear_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
        "<button onclick='cancelar_nueva_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
      "</td>" +
    "</tr>"
  );
});
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE UNA NUEVA UBICACIÓN Y LLAMAR AL SCRIPT PHP (crear_ubicacion.php) QUE LO CREE. ADEMÁS COMPROBARÁ QUE EL CÓDIGO ES VÁLIDO Y QUE SE HAYA PROPORCIONADO UNA DESCRIPCIÓN
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR UBICACIÓN NUEVA.
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_crear_ubicacion(elemento) {
  var fila, codigo, descripcion, observaciones;
  // ALMACENAR LOS DATOS QUE EL USUARIO HA INTRODUCIDO
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value'];
  descripcion = fila[1]['firstChild']['value'];
  observaciones = fila[2]['firstChild']['value'];
  // COMPROBAR QUE SE HAYA INTRODUCIDO UN CÓDIGO
  if (!codigo) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo código",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  // COMPROBAR QUE EL CÓDIGO TENGA UN FORMATO CORRECTO
  } else if (!validor_codigo(codigo)) {
    $.alert({
      title: "ERROR",
      content: "El código debe tener un formato correcto. Por ejemplo AA00.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
    return false;
  // COMPROBAR QUE SE HAYA INTRODUCIDO UNA DESCRIPCIÓN
  } else if (!descripcion) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo descripción.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  } else {
    $.post("../PHP/AJAX/UBICACIONES/crear_ubicacion.php",
    {
      campo_codigo: codigo,
      campo_descripcion: descripcion,
      campo_observaciones: observaciones
    },
    function(resultado) {
      if (resultado == "CORRECTO") {
        $.alert({
          title: "UBICACIÓN CREADA",
          content: "Se ha creado correctamente la ubicación " + codigo,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        recargar_ubicaciones(); // RECARGAR LA TABLA
      } else {
        $.alert({
          title: "ERROR",
          content: resultado,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
      }
    });
  }
} // ******** COMPLETADO ********
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR LA FILA GENERADA PARA CREAR UN NUEVA UBICACIÓN
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA CREACIÓN DE NUEVA UBICACIÓN
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_nueva_ubicacion(elemento) {
  var fila;
  fila = $(elemento).parent().parent();
  $(fila).remove(); // ******** COMPLETADO ********
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UNA UBICACION MEDIANTE UNA LLAMADA A PHP (eliminar_ubicacion.php). TRAS REALIZAR LA ACCIÓN RECARGA LA PÁGINA
  LLAMADA: ES LLAMADA CUANDO EL USUARIO PULSA EL BOTÓN DE BORRAR UBICACIÓN -> <button onclick='eliminar_ubicacion(this)'>
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN. SERÁ UTILIZADO PARA OBTENER EL CÓDIGO DE LA UBICACIÓN
*/
function eliminar_ubicacion(elemento) {
  var fila, codigo;
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value']; // OBTENER EL CÓDIGO
  $.confirm({
    title: "Eliminar ubicación",
    columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6",
    content: "¿Estás seguro de eliminar la ubicación " + codigo + " y todo lo que contiene?",
    buttons: {
      Eliminar: {
        btnClass: "btn color_intermedio",
        action: function () { // TRAS PULSAR EL BOTÓN DE "ELIMINAR" LLAMA AL FICHERO PHP DE ELIMINAR USUARIO
          $.post("../PHP/AJAX/UBICACIONES/eliminar_ubicacion.php",
          {
            campo_codigo: codigo // PASAR COMO PARÁMETRO EL CÓDIGO
          },
          function(resultado) {
            if (resultado == "CORRECTO") {
              $.alert({
                title: "Ubicación eliminada",
                content: "Se ha eliminado correctamente la ubicación " + codigo,
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
              recargar_ubicaciones(); // RECARGAR LA TABLA
            } else {
              $.alert({
                title: "ERROR",
                content: resultado,
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
            }
          });
        }
      },
      Cancelar: function () {
      },
    }
  }); // ******** COMPLETADO ********
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONVERTIR UNA FILA DE LA TABLA UBICACIONES EN MODIFICABLE
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  LLAMADA: ES LLAMADA AL PULSAR SOBRE EL BOTÓN DE MODIFICAR UBICACIÓN <button onclick='modificar_ubicacion(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function modificar_ubicacion(elemento) {
  var fila, botones, descripcion, observaciones;
  // CAMBIAR LOS INPUTS descripcion Y observaciones A EDITABLES
  fila = $(elemento).parent().siblings();
  descripcion = fila[1]['firstChild']; // ALMACENAR DESCRIPCION
  observaciones = fila[2]['firstChild']; // ALMACENAR OBSERVACIONES
  $(descripcion).removeAttr("readonly");
  $(observaciones).removeAttr("readonly");

  // CAMBIAR LOS BOTONES DE "ACCIONES" POR LOS DE CONFIRMAR O CANCELAR LA MODIFICACIÓN
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='confirmar_modificar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
    "<button onclick='cancelar_modificar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>"
  );
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE LA MODIFICACIÓN DE UNA UBICACIÓN Y LLAMAR AL SCRIPT PHP (modificar_ubicacion.php) QUE LO MODIFIQUE.
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR MODIFICACIÓN DE LA UBICACIÓN <button onclick='confirmar_modificar_ubicacion(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_modificar_ubicacion(elemento) {
  var fila, codigo, descripcion, observaciones;
  // OBTENER LOS DATOS
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value'];
  descripcion = fila[1]['firstChild']['value'];
  observaciones = fila[2]['children'][0]['value'];
  // VALIDAR QUE SE HAYA INTRODUCIDO UNA DESCRIPCIÓN
  if (!descripcion) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo descripción.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  } else {
    $.post("../PHP/AJAX/UBICACIONES/modificar_ubicacion.php", // LLAMAR AL SCRIPT PHP CON LOS DATOS RECOGIDOS
    {
      campo_codigo: codigo,
      campo_descripcion: descripcion,
      campo_observaciones: observaciones
    },
    function(resultado) {
      if (resultado == "CORRECTO") {
        $.alert({
          title: "UBICACIÓN MODIFICADA",
          content: "Se ha modificado correctamente la ubicación " + codigo,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        recargar_ubicaciones();
      } else {
        $.alert({
          title: "ERROR",
          content: resultado,
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
      }
    });
  }
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA VOLVER A HACER LA FILA DE LA TABLA UBICACIONES NO MODIFICABLE
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA MODIFICACIÓN DE UNA UBICACIÓN <button onclick='cancelar_modificar_ubicacion(this)'
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_modificar_ubicacion(elemento) {
  var botones, fila, descripcion, observaciones;
  // CAMBIAR LOS INPUTS A MODO NO EDITABLE
  fila = $(elemento).parent().siblings();
  descripcion = fila[1]['firstChild'];
  observaciones = fila[2]['firstChild'];
  $(descripcion).attr("readonly", "readonly");
  $(observaciones).attr("readonly", "readonly");

  // CAMBIAR LOS BOTONES DE CONFIRMAR O CANCELAR LA MODIFICACIÓN POR LOS HABITUALES DE "ACCIONES"
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='ver_articulos(this)' type='button' data-toggle='tooltip' data-placement='top' title='Ver inventario'><i class='fas fa-search'></i></button>" +
    "<button onclick='eliminar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar ubicación'><i class='fas fa-trash'></i></button>" +
    "<button onclick='modificar_ubicacion(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar ubicación'><i class='fas fa-pen'></i></button>"
  );
}
// DOCUMENTAAAAAAAAAAAAAAAAAAR
function ver_articulos(elemento) {
  var fila, codigo;
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value']; // OBTENER EL CÓDIGO
  $.post("../PHP/AJAX/UBICACIONES/cargar_articulos.php",
  {
    campo_codigo: codigo
  },
  function(resultado) {
    if (resultado.substring(0, 6) == "<thead") { // SI NO HA DADO NINGÚN ERROR, MOSTRAR LAS OPCIONES DE CANCELAR INVENTARIADO/CONFIRMAR Y OCULTAR LAS OPCIONES DE GESTIÓN DE UBICACIONES.
      $("#menu_acciones").hide(); // OCULTAR LA GESTIÓN DE LA UBICACIÓN
      $("#menu_acciones_inventario").show(); // MOSTRAR LAS OPCIONES DE INVENTARIAR
      $("#tabla_ubicaciones").html(resultado);
      // CARGAR LOS ARTÍCULOS DE LA BD A TRAVÉS DE AJAX
      $.post("../PHP/AJAX/UBICACIONES/cargar_articulos_disponibles.php",
      {
        campo_codigo: codigo
      },
      function(resultado) {
        $("#lista_ubicaciones_seleccionables").html(resultado);
      });
      $("#boton_confirmar_inventario").attr("onclick","confirmar_inventario('"+ codigo +"')");
    } else { // EN CASO CONTRARIO MOSTRAR EL ERROR
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    }
  });
}
// DOCUMENTAR
function add_articulo(elemento) {
  var codigo, descripcion;
  codigo = $(elemento).val(); // OBTENER CÓDIGO DEL ARTÍCULO
  descripcion = $("#" + codigo).text(); // OBTENER DESCRIPCIÓN DEL ARTÍCULO
  $("#contenido_ubicaciones").append(
                  "<tr id='" + codigo + "'>" +
                    "<td><input type='text' name='campo_codigo' value='" + codigo + "' readonly></td>" +
                    "<td><input type='text' name='campo_descripcion' value='" + descripcion + "' readonly></td>" +
                    "<td><input type='number' name='campo_cantidad' value='0'></td>" +
                    "<td>" +
                    "  <button onclick='quitar_articulo(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar artículo'><i class='fas fa-trash'></i></button>" +
                    "</td>" +
                  "</tr>"); // AÑADIR EL ARTÍCULO A LA LISTA DE ARTÍCULOS DE LA UBICACIÓN
  $("#" + codigo).remove(); // ELIMINAR EL ARTÍCULO DE LA LISTA DE ARTÍCULOS SELECCIONABLES SELECCIONABLES
}
// DOCUMENTAAAAAAAAAAAAAAAAAAR
function cancelar_inventario() {
  $("#menu_acciones").show();
  $("#menu_acciones_inventario").hide();
  recargar_ubicaciones();
}
// DOCUMENTAAAAAAAAAAAAAAAAAAR
function quitar_articulo(elemento) {
  var fila, codigo, descripcion;
  fila = $(elemento).parent().siblings();
  codigo = fila[0]['firstChild']['value']; // ALMACENAR EL CÓDIGO
  descripcion = fila[1]['firstChild']['value']; // ALMACENAR EL DESCRIPCIÓN
  $("#" + codigo).remove(); // ELIMINAR LA FILA
  $("#lista_ubicaciones_seleccionables").append("<option id='" + codigo + "' value='" + codigo + "'>" + descripcion + "</option>"); // AÑADIR EL ARTÍCULO A LA LISTA DE ARTÍCULOS AÑADIBLES
}
// DOCUMENTAAAAAAAAAAAAAAAAAAR
function confirmar_inventario(ubicacion) {
  var fila, codigo, cantidad;
  articulos_seleccionados = $("#contenido_ubicaciones").children();
  if (articulos_seleccionados.length == 0) {
    articulos_array = "ninguno";
  } else {
    articulos_array = [];
    for (var i = 0; i < articulos_seleccionados.length; i++) {
      fila = $(articulos_seleccionados[i]).children();
      codigo = fila[0]['firstChild']['value']; // ALMACENAR EL CÓDIGO
      cantidad = fila[2]['firstChild']['value'];
      if (cantidad.length == 0 || cantidad == 0) {
        $.alert({
          title: "ERROR",
          content: "El campo cantidad no se puede dejar vacío ni a 0. Si el artículo no está en la ubicacion elimínalo haciendo click en la papelera.",
          columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
        });
        return false;
      }
      articulos_array[i]= [];
      articulos_array[i][0] = codigo;
  		articulos_array[i][1] = cantidad;
    }
  }
  $.post("../PHP/AJAX/UBICACIONES/inventariar_articulos.php",
  {
    campo_ubicacion: ubicacion,
    campo_articulos: articulos_array
  },
  function(resultado) {
    if (resultado != "CORRECTO") {
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    } else {
      $.alert({
        title: "UBICACIÓN INVENTARIADA",
        content: "Se ha inventariado correctamente la ubicación",
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
      cancelar_inventario();
    }
  });
}
