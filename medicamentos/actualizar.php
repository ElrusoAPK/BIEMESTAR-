<?php

session_start();

include '../config/conexion.php';

/* =========================
   VALIDAR CONEXION
========================= */

if(!$conn){

    die("Error de conexión a PostgreSQL");

}

/* =========================
   RECIBIR DATOS
========================= */

$id = $_POST['id'] ?? '';

$medicamento = trim($_POST['medicamento'] ?? '');

$surtida = trim($_POST['surtida'] ?? '');

$lote = trim($_POST['lote'] ?? '');

$fecha_caducidad = trim($_POST['fecha_caducidad'] ?? '');

/* =========================
   VALIDAR CAMPOS
========================= */

if(

    empty($id) ||
    empty($medicamento) ||
    empty($surtida) ||
    empty($lote) ||
    empty($fecha_caducidad)

){

    echo "

    <script>

    alert('Todos los campos son obligatorios');

    window.history.back();

    </script>

    ";

    exit();

}

/* =========================
   ACTUALIZAR
========================= */

$query = "

UPDATE farmacias_bienestar

SET

medicamento = $1,
surtida = $2,
lote = $3,
fecha_caducidad = $4

WHERE id = $5

";

$result = pg_query_params(

    $conn,

    $query,

    array(

        $medicamento,
        $surtida,
        $lote,
        $fecha_caducidad,
        $id

    )

);

/* =========================
   RESULTADO
========================= */

if($result){

    echo "

    <!DOCTYPE html>
    <html>
    <head>

    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

    </head>
    <body>

    <script>

    Swal.fire({

        icon:'success',

        title:'Actualizado',

        text:'Medicamento actualizado correctamente',

        confirmButtonColor:'#611232'

    }).then(()=>{

        window.location='index.php';

    });

    </script>

    </body>
    </html>

    ";

}else{

    echo "

<!DOCTYPE html>
    <html>
    <head>

    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

    </head>
    <body>

    <script>

    Swal.fire({

        icon:'error',

        title:'Error',

        text:'No se pudo actualizar el registro'

    }).then(()=>{

        window.history.back();

    });

    </script>

    </body>
    </html>

    ";

}

?>