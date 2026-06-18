<?php

session_start();

include '../config/conexion.php';

/* =========================
   VALIDAR SELECCION
========================= */

if(
    !isset($_POST['ids']) ||
    empty($_POST['ids'])
){

    header("Location: index.html");
    exit();

}

$ids = $_POST['ids'];

/* =========================
   ELIMINAR REGISTROS
========================= */

foreach($ids as $id){

    pg_query_params(

        $conn,

        "DELETE FROM farmacias_bienestar
         WHERE id = $1",

        array($id)

    );

}

/* =========================
   REGRESAR
========================= */

header("Location: index.php");

exit();

?>