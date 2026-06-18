<?php

session_start();

include '../config/conexion.php';

/* =========================
   OBTENER ID
========================= */

$id = $_GET['id'] ?? 0;

/* =========================
   CONSULTAR REGISTRO
========================= */

$query = "

SELECT *
FROM farmacias_bienestar
WHERE id = $1

";

$result = pg_query_params(
    $conn,
    $query,
    array($id)
);

$data = pg_fetch_assoc($result);

if(!$data){

    die("Registro no encontrado");

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Medicamento</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f4f6f9;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.form-box{

    width:100%;
    max-width:700px;

    background:#fff;

    padding:35px;

    border-radius:15px;

    box-shadow:0 10px 25px rgba(0,0,0,.08);

}

h1{

    text-align:center;

    color:#611232;

    margin-bottom:30px;

}

.form-group{

    margin-bottom:20px;

}

label{

    display:block;

    margin-bottom:8px;

    font-weight:600;

    color:#444;

}

input{

    width:100%;

    padding:12px;

    border:1px solid #ddd;

    border-radius:10px;

    font-size:15px;

}

input:focus{

    outline:none;

    border-color:#611232;

    box-shadow:0 0 8px rgba(97,18,50,.2);

}

.botones{

    display:flex;

    gap:15px;

    margin-top:25px;

}

.btn{

    flex:1;

    padding:14px;

    border:none;

    border-radius:10px;

    text-decoration:none;

    text-align:center;

    font-weight:bold;

    cursor:pointer;

    transition:.3s;

}

.btn-actualizar{

    background:#611232;

    color:white;

}

.btn-actualizar:hover{

    background:#7b1a40;

}

.btn-cancelar{

    background:#6c757d;

    color:white;

}

.btn-cancelar:hover{

    background:#555;

}

</style>

</head>

<body>

<div class="form-box">

<h1>

<i class="fa-solid fa-pen-to-square"></i>

Editar Medicamento

</h1>

<form action="actualizar.php" method="POST">

<input
type="hidden"
name="id"
value="<?php echo $data['id']; ?>">

<div class="form-group">

<label>

Medicamento

</label>

<input
type="text"
name="medicamento"
required
value="<?php echo htmlspecialchars($data['medicamento']); ?>">

</div>

<div class="form-group">

<label>

Cantidad Surtida

</label>

<input
type="number"
name="surtida"
required
value="<?php echo $data['surtida']; ?>">

</div>

<div class="form-group">

<label>

Lote

</label>

<input
type="text"
name="lote"
required
value="<?php echo htmlspecialchars($data['lote']); ?>">

</div>

<div class="form-group">

<label>

Fecha de Caducidad

</label>

<input
type="date"
name="fecha_caducidad"
required
value="<?php echo $data['fecha_caducidad']; ?>">

</div>

<div class="botones">

<button
type="submit"
class="btn btn-actualizar">

<i class="fa-solid fa-floppy-disk"></i>

Actualizar

</button>

<a
href="index.php"
class="btn btn-cancelar">

<i class="fa-solid fa-arrow-left"></i>

Cancelar

</a>

</div>

</form>

</div>

</body>

</html>