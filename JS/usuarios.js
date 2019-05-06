/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA COMPROBAR SI UN EMAIL TIENE EL FORMATO CORRECTO MEDIANTE UNA EXPRESIÓN REGULAR
  RESULTADO: DEVUELVE FALSE SI NO TIENE UN FORMATO CORRECTO O TRUE SI SÍ QUE LO TIENE
  LLAMADA: ES LLAMADA AL CREAR UN USUARIO.
  PARÁMETROS:
    - EMAIL: CADENA QUE VALIDAR
*/
function validar_email(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECARGAR LA TABLA QUE CONTIENE INFORMACIÓN SOBRE LOS USUARIOS.
  LLAMADA: ES LLAMADA CADA VEZ QUE SE REALIZA UN CAMBIO VISIBLE EN LA TABLA (BORRADO, MODIFICACIÓN O ADICIÓN DE USUARIOS)
  PARÁMETROS:
    - FILTRO: CADENA POR LA QUE FILTRAR EL RESULTADO, SI NO SE LE PASA TOMA EL VALOR = "ninguno"
*/
function recargar_usuarios(filtro = "ninguno"){
  $.post("../PHP/AJAX/USUARIOS/recargar_usuarios.php",
  {
    campo_filtro: filtro
  },
  function(resultado) {
    $("#contenido_usuarios").html(resultado); //EL RESULTADO DEL SCRIPT PHP SUSTITUYE EL CONTENIDO DE LA TABLA
  });
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA FILTRAR LA TABLA USUARIOS POR NOMBRE/EMAIL. SI EL CAMPO 'campo_buscar' ESTÁ VACÍO MUESTRA LA TABLA ENTERA
  LLAMADA: ES LLAMADA CADA VEZ QUE SE HACE CLICK EN EL BOTÓN <button onclick='buscar_usuarios()'>
  PARÁMETROS:
*/
function buscar_usuarios() {
  var texto;
  texto = $("#campo_buscar").val();
  if (texto.length > 0) {
    recargar_usuarios(texto);
  } else {
    recargar_usuarios();
  }
}
// AL HACER CLICK EN EL BOTÓN DE CREAR USUARIO SE AÑADE UNA FILA A LA TABLA CON UN FORM
$("#crear_usuario").click(function(){
  $("#contenido_usuarios").append(
    "<tr>" +
      "<td><input type='text' name='campo_email' class=''></td>" +
      "<td><input type='text' name='campo_nombre'></td>" +
      "<td>" +
        "<select class='custom-select'>" +
          "<option value='estandar'>Estándar</option> " +
          "<option value='editor'>Editor</option> " +
          "<option value='administrador'>Administrador</option>" +
        "</select>" +
      "</td>" +
      "<td>" +
        "<button onclick='confirmar_crear_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
        "<button onclick='cancelar_nuevo_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>" +
      "</td>" +
    "</tr>"
  )
});
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE UN NUEVO USUARIO Y LLAMAR AL SCRIPT PHP QUE LO CREE. ADEMÁS COMPROBARÁ QUE EL EMAIL ES VALIDO, QUE LAS CONTRASEÑAS CONCUERDEN Y QUE EL TIPO ES CORRECTO.
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR USUARIO NUEVO.
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_crear_usuario(elemento) {
  var fila, email, nombre, tipo, password_1, password_2;
  // ALMACENAR LOS DATOS QUE EL USUARIO HA INTRODUCIDO
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  nombre = fila[1]['firstChild']['value'];
  tipo = fila[2]['firstChild']['value'];
  // COMPROBAR QUE SE HAYA INTRODUCIDO UN EMAIL
  if (!email) {
    $.alert({
      title: "ERROR",
      content: "Se debe rellenar el campo email",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
      return false;
  // COMPROBAR QUE EL EMAIL TENGA UN FORMATO CORRECTO
  } else if (!validar_email(email)) {
    $.alert({
      title: "ERROR",
      content: "El email debe tener un formato válido.",
      columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
    });
    return false;
  } else {
    $.confirm({ // MUESTRA UN FORMULARIO DONDE INTRODUCIR LA CONTRASEÑA DEL NUEVO USUARIO
      title: "Introduce la contraseña para el usuario " + email + ": ",
      columnClass: "col-sm-12 col-md-10 col-lg-8 col-xl-6",
      content: "" +
        "<form action='' class='formName'>" +
          "<div class='form-group'>" +
            "<input type='password' id='campo_password_1' placeholder='Contraseña' class='name form-control'>" +
            "<br>" +
            "<input type='password' id='campo_password_2' placeholder='Repite la contraseña' class='name form-control'>" +
          "</div>" +
        "</form>",
      buttons: {
        Crear: {
          btnClass: "btn color_intermedio",
          action: function () { // ENVÍA LOS DATOS AL SCRIPT PHP, ANTES VALIDA LAS CONTRASEÑAS INTRODUCIDAS
            password_1 = $("#campo_password_1").val();
            password_2 = $("#campo_password_2").val();
            // COMPROBAR QUE SE HAYAN INTRODUCIDO AMBAS
            if (!password_1 || !password_2) {
              $.alert({
                title: "ERROR",
                content: "Se debe rellenar ambos campos.",
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
                return false;
            // COMPROBAR QUE SEAN IGUALES
            } else if (password_1 != password_2) {
              $.alert({
                title: "ERROR",
                content: "Las contraseñas deben coincidir.",
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
              return false;
            } else { // SI TODO ES CORRECTO LLAMA AL SCRIPT PHP
              $.post("../PHP/AJAX/USUARIOS/crear_usuario.php",
              {
                campo_email: email,
                campo_password: password_1,
                campo_nombre: nombre,
                campo_tipo: tipo
              },
              function(resultado) {
                if (resultado == "CORRECTO") {
                  $.alert({
                    title: "USUARIO CREADO",
                    content: "Se ha creado correctamente al usuario " + email,
                    columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
                  });
                  recargar_usuarios(); // RECARGAR LA TABLA
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
        },
        Cancelar: function () {
        },
      }
    });
  }
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR LA FILA GENERADA PARA CREAR UN NUEVO USUARIO
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA CREACIÓN DE NUEVO USUARIO
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_nuevo_usuario(elemento) {
  var fila;
  fila = $(elemento).parent().parent();
  $(fila).remove();
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UN USUARIO MEDIANTE UNA LLAMADA A PHP (eliminar_usuario.php). TRAS REALIZAR LA ACCIÓN RECARGA LA PÁGINA
  LLAMADA: ES LLAMADA CUANDO EL USUARIO PULSA EL BOTÓN DE BORRAR USUARIO -> <button onclick='eliminar_usuario(this)'>
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN. SERÁ UTILIZADO PARA OBTENER EL EMAIL
*/
function eliminar_usuario(elemento) {
  var fila, email;
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value']; // OBTENER EL EMAIL
  $.confirm({
    title: "Eliminar usuario",
    columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6",
    content: "¿Estás seguro de eliminar al usuario " + email + "?",
    buttons: {
      Eliminar: {
        btnClass: "btn color_intermedio",
        action: function () { // TRAS PULSAR EL BOTÓN DE "ELIMINAR" LLAMA AL FICHERO PHP DE ELIMINAR USUARIO
          $.post("../PHP/AJAX/USUARIOS/eliminar_usuario.php",
          {
            campo_email: email // PASAR COMO PARÁMETRO EL email
          },
          function(resultado) {
            if (resultado == "CORRECTO") {
              $.alert({
                title: "Usuario eliminado",
                content: "Se ha eliminado correctamente al usuario " + email,
                columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
              });
              recargar_usuarios(); // RECARGAR LA TABLA
            } else {
              $.alert({
                title: "Error",
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
  });
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA CONVERTIR UNA FILA DE LA TABLA USUARIOS EN MODIFICABLE
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  LLAMADA: ES LLAMADA AL PULSAR SOBRE EL BOTÓN DE MODIFICAR USUARIO<button onclick='modificar_usuario(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function modificar_usuario(elemento) {
  var fila, botones, nombre, tipo;
  // CAMBIAR LOS INPUTS nombre Y tipo A EDITABLES
  fila = $(elemento).parent().siblings();
  nombre = fila[1]['firstChild']; // ALMACENAR NOMBRE
  tipo = fila[2]['children'][0]; // ALMACENAR TIPO DE USUARIO
  $(nombre).removeAttr("readonly");
  $(tipo).removeAttr("disabled");

  // CAMBIAR LOS BOTONES DE "ACCIONES" POR LOS DE CONFIRMAR O CANCELAR LA MODIFICACIÓN
  botones = $(elemento).parent();
  $(botones).html(
    "<button onclick='confirmar_modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Confirmar'><i class='fas fa-check'></i></button>" +
    "<button onclick='cancelar_modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Cancelar'><i class='fas fa-times'></i></button>"
  );
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA RECOGER LOS DATOS DE LA MODIFICACIÓN DE UN USUARIO Y LLAMAR AL SCRIPT PHP (modificar_usuario.php) QUE LO MODIFIQUE.
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CONFIRMAR MODIFICACIÓN DEL USUARIO.
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function confirmar_modificar_usuario(elemento) {
  var fila, email, nombre, tipo;

  // OBTENER LOS DATOS
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  nombre = fila[1]['firstChild']['value'];
  tipo = fila[2]['children'][0]['value'];
  $.post("../PHP/AJAX/USUARIOS/modificar_usuario.php", // LLAMAR AL SCRIPT PHP CON LOS DATOS RECOGIDOS
  {
    campo_email: email,
    campo_nombre: nombre,
    campo_tipo: tipo
  },
  function(resultado) {
    if (resultado == "CORRECTO") {
      $.alert({
        title: "USUARIO MODIFICADO",
        content: "Se ha modificado correctamente al usuario " + email,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
      recargar_usuarios();
    } else {
      $.alert({
        title: "ERROR",
        content: resultado,
        columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
      });
    }
  });
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA VOLVER A HACER LA FILA DE LA TABLA USUARIOS NO MODIFICABLE
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE CANCELAR LA MODIFICACIÓN DE UN USUARIO <button onclick='cancelar_modificar_usuario(this)'
  RESULTADO: NO DEVUELVE NINGÚN VALOR
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function cancelar_modificar_usuario(elemento) {
  var botones, fila, tipo, contenido_acciones;
  // CAMBIAR LOS INPUTS A MODO NO EDITABLE
  fila = $(elemento).parent().siblings();
  nombre = fila[1]['firstChild'];
  tipo = fila[2]['children'][0];
  $(nombre).attr("readonly", "readonly");
  $(tipo).attr("disabled", "disabled");

  // CAMBIAR LOS BOTONES DE CONFIRMAR O CANCELAR LA MODIFICACIÓN POR LOS HABITUALES DE "ACCIONES"
  if (tipo.value == "editor") contenido_acciones = "<button onclick='ver_ubicaciones(this)' type='button' data-toggle='tooltip' data-placement='top' title='Ver localizaciones'><i class='fas fa-search'></i></button>"; // SOLO MOSTRAMOS EL BOTÓN VER UBICACIONES SI ES EDITOR
  else contenido_acciones = "";
  botones = $(elemento).parent();
  contenido_acciones = contenido_acciones.concat(
    "<button onclick='eliminar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Eliminar usuario'><i class='fas fa-trash'></i></button>" +
    "<button onclick='modificar_usuario(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar usuario'><i class='fas fa-pen'></i></button>" +
    "<button onclick='modificar_password(this)' type='button' data-toggle='tooltip' data-placement='top' title='Modificar contraseña'><i class='fas fa-key'></i></button>"
  );
  $(botones).html(contenido_acciones);
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA SOLICITAR UNA NUEVA CONTRASEÑA Y EJECUTAR EL SCRIPT PHP modificar_password.php PARA MODIFICAR LA CONTRASEÑA DE UN USUARIO.
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE MODIFICAR PASSWORD <button onclick='modificar_password(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function modificar_password(elemento) {
  var fila, email, password_1, password_2;
  // ALMACENAR EL EMAIL PARA SABER QUÉ USUARIO DEBEMOS MODIFICAR LA PASSWORD
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];
  // MOSTRAR UNA VENTANA PARA QUE ESCRIBA LA NUEVA CONTRASEÑA
  $.confirm({
    title: "Introduce la nueva contraseña para el usuario " + email + ": ",
    columnClass: "col-sm-12 col-md-10 col-lg-6 col-xl-8",
    content: "" +
      "<form action='' class='formName'>" +
        "<div class='form-group'>" +
          "<input type='password' id='campo_password_1' placeholder='Contraseña' class='name form-control'>" +
          "<br>" +
          "<input type='password' id='campo_password_2' placeholder='Repite la contraseña' class='name form-control'>" +
        "</div>" +
      "</form>",
    buttons: {
      Modificar: {
        btnClass: "btn color_intermedio",
        action: function () {
          password_1 = $("#campo_password_1").val();
          password_2 = $("#campo_password_2").val();
          // COMPROBAR QUE SE HAYAN INTRODUCIDO AMBAS
          if (!password_1 || !password_2) {
            $.alert({
              title: "ERROR",
              content: "Debes de rellenar ambos campos.",
              columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
            });
            return false;
          // COMPROBAR QUE SEAN IGUALES
          } else if (password_1 != password_2) {
            $.alert({
              title: "ERROR",
              content: "Las contraseñas deben de coincidir.",
              columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
            });
            return false;
          } else {
            $.post("../PHP/AJAX/USUARIOS/modificar_password.php",
            {
              campo_email: email,
              campo_password: password_1
            },
            function(resultado) {
              if (resultado == "CORRECTO") {
                $.alert({
                  title: "ERROR",
                  content: "Se ha modificado correctamente la contraseña del usuario " + email + " y se le ha notificado por correo",
                  columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
                });
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
      },
      Cancelar: function () {
      },
    }
  });

}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA MODIFICAR LAS UBICACIONES QUE PUEDE GESTIONAR UN USUARIO. INTERACTÚA CON DOS SCRIPTS PHP:
               ver_ubicaciones_administrador.php para cargar las ubicaciones y modificar_ubicaciones_administrador.php para realizar los cambios efectuados.
               UTILIZA LAS FUNCIONES JS eliminar_ubicacion Y add_ubicacion PARA QUE EL USUARIO PUEDA INTERACTUAR CON LAS UBICACIONES (AÑADIR Y QUITAR)
  LLAMADA: ES LLAMADA AL HACER CLICK EN EL BOTÓN DE VER UBICACIONES <button onclick='ver_ubicaciones(this)'
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function ver_ubicaciones(elemento) {
  var fila, email, ubicaciones_seleccionadas, ubicaciones_array;
  // ALMACENAR EL EMAIL PARA SABER QUÉ USUARIO DEBEMOS MOSTRAR SUS UBICACIONES
  fila = $(elemento).parent().siblings();
  email = fila[0]['firstChild']['value'];

  // OBTENER POR AJAX LAS LOCALIZACIONES QUE EL USUARIO PUEDE GESTIONAR
  $.post("../PHP/AJAX/USUARIOS/ver_ubicaciones_administrador.php",
  {
    campo_email: email
  },
  function(resultado) {
    $.confirm({
      title: "Ubicaciones del usuario " + email + ": ",
      columnClass: "col-sm-12 col-md-12 col-lg-12 col-xl-6",
      content: resultado, // MOSTRAR LAS UBICACIONES CARGADAS DESDE EL SCRIPT PHP
      buttons: {
        Guardar_cambios: {
          btnClass: "btn color_intermedio",
          action: function () {
            // GUARDAMOS EN LA VAR ubicaciones_seleccionadas LAS UBICACIONES QUE EL USUARIO VA A GESTIONAR
            ubicaciones_seleccionadas = $("#lista_ubicaciones_seleccionadas").children();
            if (ubicaciones_seleccionadas.length == 0) {
              ubicaciones_array = "ninguno";
            } else {
              ubicaciones_array = {};
              for (var i = 0; i < ubicaciones_seleccionadas.length; i++) {
                ubicaciones_array[i] = $(ubicaciones_seleccionadas[i]).attr("id");
              }
            }
            // MODIFICAR POR AJAX LAS LOCALIZACIONES QUE EL USUARIO PUEDE GESTIONA
            $.post("../PHP/AJAX/USUARIOS/modificar_ubicaciones_administrador.php",
            {
              campo_email: email,
              campo_ubicaciones: ubicaciones_array
            },
            function(resultado) {
              if (resultado == "CORRECTO") {
                $.alert({
                  title: "USUARIO MODIFICADO",
                  content: "Se ha modificado correctamente las ubicaciones que gestiona el usuario " + email,
                  columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
                });
              } else {
                $.alert({
                  title: "ERROR",
                  content: "resultado",
                  columnClass: "col-sm-12 col-md-12 col-lg-6 col-xl-6"
                });
              }
            });
          }
        },
        Cancelar: function () {
        },
      }
    });
  });
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA ELIMINAR UNA UBICACIÓN DE LA LISTA DE UBICACIONES GESTIONADAS POR EL USUARIO.
  LLAMADA: ES LLAMADA AL HACER CLICK SOBRE UNA UBICACIÓN SELECCIONADA
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function eliminar_ubicacion(elemento) {
  var codigo, descripcion;
  codigo = $(elemento).attr("id"); // OBTENER EL ID (código de la ubicación)
  descripcion = $(elemento).text(); // OBTENER EL TEXTO (descripción de la ubicación)
  $("#lista_ubicaciones_seleccionables").append("<option id='" + codigo + "' value='" + codigo + "'>" + descripcion + "</option>"); // AÑADIR LA UBICACIÓN A LA LISTA DE UBICACIONES SELECCIONABLES
  $(elemento).remove(); // ELIMINAR LA UBICACIÓN DE LAS UBICACIONES SELECCIONADAS
}
/**
  DESCRIPCIÓN: FUNCIÓN UTILIZADA PARA AÑADIR UNA UBICACIÓN A LA LISTA DE UBICACIONES GESTIONADAS POR EL USUARIO.
  LLAMADA: ES LLAMADA AL HACER CLICK SOBRE UNA UBICACIÓN DE LA LISTA DE UBICACIONES DISPONIBLES PARA AÑADIR
  PARÁMETROS:
    - ELEMENTO: ELEMENTO DEL ÁRBOL DOM QUE HA LLAMADO A LA FUNCIÓN
*/
function add_ubicacion(elemento) {
  var codigo, descripcion;
  codigo = $(elemento).val(); // OBTENER CÓDIGO DE LA UBICACIÓN
  descripcion = $("#" + codigo).text(); // OBTENER DESCRIPCIÓN DE LA UBICACIÓN
  $("#lista_ubicaciones_seleccionadas").append("<li onclick='eliminar_ubicacion(this)' class='list-group-item' id='" + codigo + "'>" + descripcion + "</li>"); // AÑADIR A LA UBICACIÓN A LA LISTA DE UBICACIONES SELECCIONADAS
  $("#" + codigo).remove(); // ELIMINAR LA UBICACIÓN DE LAS UBICACIONES SELECCIONABLES
}
