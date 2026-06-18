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

$medicamento = trim($_POST['medicamento'] ?? '');
$surtida = trim($_POST['surtida'] ?? '');
$lote = trim($_POST['lote'] ?? '');
$fecha_caducidad = trim($_POST['fecha_caducidad'] ?? '');

/* =========================
   VALIDAR CAMPOS
========================= */

if(
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
   INSERTAR REGISTRO
========================= */

$query = "

INSERT INTO farmacias_bienestar(

medicamento,
surtida,
lote,
fecha_caducidad

)

VALUES(

$1,
$2,
$3,
$4

)

";

/* =========================
   EJECUTAR
========================= */

$result = pg_query_params(

    $conn,

    $query,

    array(

        $medicamento,
        $surtida,
        $lote,
        $fecha_caducidad

    )

);

/* =========================
   RESULTADO
========================= */

if($result){

?>

<script>

alert("Medicamento registrado correctamente");

window.location.href="index.php";

</script>

<?php

}else{

?>

<script>

alert("Error al guardar el medicamento");

window.history.back();

</script>

<?php

}

?>