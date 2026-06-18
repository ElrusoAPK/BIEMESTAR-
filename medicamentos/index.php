<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: ../index.php");
    exit();
}

include '../config/conexion.php';


/* =========================
   BUSCADOR
========================= */

$buscar="";

if(isset($_GET['buscar']) && $_GET['buscar']!=""){

    $buscar=trim($_GET['buscar']);

    $result=pg_query_params($conn,

    "

    SELECT *

    FROM farmacias_bienestar

    WHERE

    medicamento ILIKE $1

    OR localidad ILIKE $1

    OR municipio ILIKE $1

    OR domicilio ILIKE $1

    ORDER BY id DESC

    ",

    array("%$buscar%")

    );

}else{

    $result=pg_query($conn,"

    SELECT *

    FROM farmacias_bienestar

    ORDER BY id DESC

    ");

}


/* =========================
   KPIS
========================= */

$totalRegistros = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(*) total
FROM farmacias_bienestar
")
);

$totalMedicamentos = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT medicamento) total
FROM farmacias_bienestar
")
);

$totalLocalidades = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT localidad) total
FROM farmacias_bienestar
")
);

$totalMunicipios = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT municipio) total
FROM farmacias_bienestar
")
);


/* =========================
   MEDICAMENTO TOP
========================= */

$topMedicamento = pg_fetch_assoc(
pg_query($conn,"

SELECT medicamento,
COUNT(*) total

FROM farmacias_bienestar

GROUP BY medicamento

ORDER BY total DESC

LIMIT 1

")
);


/* =========================
   ESCASEZ
========================= */

$escasos = pg_query($conn,"

SELECT medicamento,
COUNT(*) cantidad

FROM farmacias_bienestar

GROUP BY medicamento

HAVING COUNT(*) < 20

ORDER BY cantidad ASC

");

$totalEscasos = pg_num_rows($escasos);


/* =========================
   CRITICOS
========================= */

$criticos = pg_query($conn,"

SELECT medicamento,
COUNT(*) cantidad

FROM farmacias_bienestar

GROUP BY medicamento

HAVING COUNT(*) < 10

");

$totalCriticos = pg_num_rows($criticos);


/* =========================
   INDICE ABASTECIMIENTO
========================= */

$indiceAbastecimiento =
$totalMedicamentos['total']>0

?

round(
(
($totalMedicamentos['total']-$totalEscasos)
/
$totalMedicamentos['total']
)*100
)

:0;



if($indiceAbastecimiento>=85){

    $nivel="BAJO";
    $color="success";

}elseif($indiceAbastecimiento>=60){

    $nivel="MEDIO";
    $color="warning";

}else{

    $nivel="ALTO";
    $color="danger";

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>
Sistema Inteligente de Gestión de Medicamentos
</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>

:root{

    --guinda:#611232;
    --guinda2:#8a1538;
    --gris:#f4f6f9;

}

body{

    background:var(--gris);
    font-family:'Segoe UI',sans-serif;

}


/* =========================
   MENU
========================= */

.menu-btn{

    position:fixed;
    top:12px;
    right:15px;

    width:35px;
    height:35px;

    border:none;

    border-radius:8px;

    background:var(--guinda);

    color:white;

    z-index:99999;

}


.sidebar{

    position:fixed;

    top:0;
    right:-260px;

    width:250px;
    height:100vh;

    background:var(--guinda);

    padding:60px 20px;

    transition:.3s;

    z-index:99998;

}

.sidebar.activo{

    right:0;

}

.sidebar h2{

    color:white;
    text-align:center;
    margin-bottom:25px;

}

.sidebar a{

    display:block;

    padding:12px;

    margin-bottom:12px;

    border-radius:10px;

    background:#ffffff20;

    color:white;

    text-decoration:none;

}

.sidebar a:hover{

    background:white;
    color:var(--guinda);

}

#fondo{

    position:fixed;

    width:100%;
    height:100%;

    background:#0008;

    display:none;

    z-index:99997;

}

#fondo.activo{

    display:block;

}


/* =========================
   CONTENIDO
========================= */

.contenedor{

    padding:30px;
    padding-top:65px;

}


/* =========================
   BANNER
========================= */

.banner{

    background:linear-gradient(
    135deg,
    #611232,
    #8a1538
    );

    color:white;

    border-radius:20px;

    padding:30px;

    margin-bottom:25px;

    box-shadow:0 5px 20px rgba(0,0,0,.15);

}

.banner h1{

    font-size:30px;
    font-weight:700;

}

.banner p{

    margin-top:10px;
    opacity:.9;

}


/* =========================
   KPI
========================= */

.kpi{

    background:white;

    border-radius:18px;

    padding:25px;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    transition:.3s;

}

.kpi:hover{

    transform:translateY(-5px);

}

.kpi i{

    font-size:30px;
    color:#611232;

}

.kpi h5{

    margin-top:15px;
    color:#666;

}

.kpi h2{

    color:#611232;
    font-weight:bold;

}


/* =========================
   ALERTAS
========================= */

.alerta-box{

    background:white;

    border-radius:18px;

    padding:25px;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    height:100%;

}

.alerta-box h4{

    color:#611232;

}


/* =========================
   TABLA
========================= */

.tabla-box{

    background:white;

    border-radius:18px;

    padding:20px;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    margin-top:25px;

}

.table thead{

    background:#611232;

    color:white;

}

.badge-med{

    background:#198754;

    color:white;

    padding:8px 12px;

    border-radius:30px;

}

.btn-editar{

    background:#0d6efd;

    color:white;

}

.btn-eliminar{

    background:#dc3545;

    color:white;

}

</style>

</head>

<body>



<button class="menu-btn"
onclick="abrirMenu()">

☰

</button>


<div id="fondo"
onclick="cerrarMenu()"></div>


<div class="sidebar"
id="sidebar">

<h2>
BIENESTAR
</h2>

<a href="../dashboard.php">
Dashboard
</a>

<a href="../pacientes/index.php">
Pacientes
</a>

<a href="index.php">
Medicamentos
</a>

<a href="../reportes/index.php">
Reportes
</a>

<a href="../logouth.php">
Salir
</a>

</div>



<div class="contenedor">


<div class="banner">

<h1>
Centro Inteligente de Medicamentos
</h1>

<p>

Análisis, monitoreo y visualización de datos para la gestión estratégica de medicamentos.

</p>

</div>



<div class="row g-4">

<div class="col-lg-3">

<div class="kpi">

<i class="fa-solid fa-database"></i>

<h5>Registros</h5>

<h2>
<?=$totalRegistros['total']?>
</h2>

</div>

</div>



<div class="col-lg-3">

<div class="kpi">

<i class="fa-solid fa-capsules"></i>

<h5>Medicamentos</h5>

<h2>
<?=$totalMedicamentos['total']?>
</h2>

</div>

</div>



<div class="col-lg-3">

<div class="kpi">

<i class="fa-solid fa-location-dot"></i>

<h5>Localidades</h5>

<h2>
<?=$totalLocalidades['total']?>
</h2>

</div>

</div>



<div class="col-lg-3">

<div class="kpi">

<i class="fa-solid fa-map"></i>

<h5>Municipios</h5>

<h2>
<?=$totalMunicipios['total']?>
</h2>

</div>

</div>

</div>



<div class="row mt-4">

<div class="col-lg-6">

<div class="alerta-box">

<h4>
Centro de Alertas
</h4>

<hr>

<div class="alert alert-danger">

Medicamentos críticos:

<strong>
<?=$totalCriticos?>
</strong>

</div>

<div class="alert alert-warning">

Medicamentos escasos:

<strong>
<?=$totalEscasos?>
</strong>

</div>

</div>

</div>



<div class="col-lg-6">

<div class="alerta-box">

<h4>
Indicadores Ejecutivos
</h4>

<hr>

<p>

Medicamento más solicitado:

<strong>
<?=$topMedicamento['medicamento']?>
</strong>

</p>

<p>

Solicitudes registradas:

<strong>
<?=$topMedicamento['total']?>
</strong>

</p>

<p>

Índice de abastecimiento:

<strong>
<?=$indiceAbastecimiento?>%
</strong>

</p>

<span class="badge bg-<?=$color?>">
Riesgo <?=$nivel?>
</span>

</div>

</div>

</div>
    
<!-- =========================
     BUSCADOR
========================= -->

<div class="tabla-box">

<form method="GET" class="row g-3 mb-4">

<div class="col-md-10">

<input
type="text"
name="buscar"
class="form-control form-control-lg"
placeholder="Buscar medicamento, localidad, municipio o domicilio..."
value="<?=htmlspecialchars($buscar)?>">

</div>

<div class="col-md-2 d-grid">

<button class="btn btn-danger btn-lg">

<i class="fa-solid fa-magnifying-glass"></i>
 Buscar

</button>

</div>

</form>



<!-- =========================
     TABLA
========================= -->

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>

<th>ID</th>

<th>Medicamento</th>

<th>Domicilio</th>

<th>Localidad</th>

<th>Municipio</th>

<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php while($row=pg_fetch_assoc($result)){ ?>

<tr>

<td>

<?=$row['id']?>

</td>

<td>

<span class="badge-med">

<?=htmlspecialchars($row['medicamento'])?>

</span>

</td>

<td>

<?=htmlspecialchars($row['domicilio'])?>

</td>

<td>

<?=htmlspecialchars($row['localidad'])?>

</td>

<td>

<?=htmlspecialchars($row['municipio'])?>

</td>

<td>

<a
href="editar.php?id=<?=$row['id']?>"
class="btn btn-sm btn-primary">

<i class="fa-solid fa-pen"></i>

</a>

<a
href="eliminar.php?id=<?=$row['id']?>"
class="btn btn-sm btn-danger"
onclick="return confirm('¿Eliminar registro?')">

<i class="fa-solid fa-trash"></i>

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>



<!-- =========================
     PANEL ANALITICO
========================= -->

<div class="row mt-4">

<div class="col-lg-12">

<div class="alerta-box">

<h4>
🤖 Hallazgos Automáticos
</h4>

<hr>

<ul class="list-group">

<li class="list-group-item">

Se identificaron

<strong>

<?=$totalMedicamentos['total']?>

</strong>

medicamentos distintos en la base de datos.

</li>

<li class="list-group-item">

Actualmente existen

<strong>

<?=$totalEscasos?>

</strong>

medicamentos con riesgo de escasez.

</li>

<li class="list-group-item">

El medicamento más solicitado es

<strong>

<?=$topMedicamento['medicamento']?>

</strong>

con

<strong>

<?=$topMedicamento['total']?>

</strong>

registros.

</li>

<li class="list-group-item">

El índice general de abastecimiento es de

<strong>

<?=$indiceAbastecimiento?>%

</strong>

</li>

<li class="list-group-item">

Nivel de riesgo detectado:

<span class="badge bg-<?=$color?>">

<?=$nivel?>

</span>

</li>

</ul>

</div>

</div>

</div>



<!-- =========================
     FOOTER
========================= -->

<div class="text-center text-muted mt-4 mb-3">

Sistema Inteligente para el Análisis y Visualización de Datos de Farmacias Bienestar

<br>

Proyecto de Titulación | Ingeniería en Informatica

</div>

</div>



<!-- =========================
     JAVASCRIPT MENU
========================= -->

<script>

function abrirMenu(){

document
.getElementById("sidebar")
.classList
.add("activo");

document
.getElementById("fondo")
.classList
.add("activo");

}

function cerrarMenu(){

document
.getElementById("sidebar")
.classList
.remove("activo");

document
.getElementById("fondo")
.classList
.remove("activo");

}

</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
