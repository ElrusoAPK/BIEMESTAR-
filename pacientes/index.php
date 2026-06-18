<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

if(!isset($_SESSION['usuario'])){
    header("Location: ../index.php");
    exit();
}

include '../config/conexion.php';

if(!$conn){
    die("Error de conexión PostgreSQL");
}

/* =====================================
   FILTROS
===================================== */

$buscar = $_GET['buscar'] ?? '';
$localidadFiltro = $_GET['localidad'] ?? '';
$medicamentoFiltro = $_GET['medicamento'] ?? '';

$where = "WHERE 1=1";
$params = [];
$contador = 1;

if(!empty($buscar)){

    $where .= "
    AND (
        nombres_derechohabiente ILIKE $".$contador."
        OR apellido_paterno_derechohabiente ILIKE $".$contador."
        OR apellido_materno_derechohabiente ILIKE $".$contador."
        OR telefono ILIKE $".$contador."
        OR domicilio ILIKE $".$contador."
    )";

    $params[] = "%".$buscar."%";
    $contador++;
}

if(!empty($localidadFiltro)){

    $where .= "
    AND localidad = $".$contador;

    $params[] = $localidadFiltro;
    $contador++;
}

if(!empty($medicamentoFiltro)){

    $where .= "
    AND medicamento = $".$contador;

    $params[] = $medicamentoFiltro;
    $contador++;
}

/* =====================================
   TABLA PRINCIPAL
===================================== */

$sqlPacientes = "
SELECT *
FROM farmacias_bienestar
$where
ORDER BY id DESC
";

if(count($params)>0){

    $result = pg_query_params(
        $conn,
        $sqlPacientes,
        $params
    );

}else{

    $result = pg_query(
        $conn,
        $sqlPacientes
    );
}

/* =====================================
   KPIs PRINCIPALES
===================================== */

$totalPacientes = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(*) total
FROM farmacias_bienestar
"));

$totalMedicamentos = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT medicamento) total
FROM farmacias_bienestar
"));

$totalLocalidades = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT localidad) total
FROM farmacias_bienestar
"));

$totalMunicipios = pg_fetch_assoc(
pg_query($conn,"
SELECT COUNT(DISTINCT municipio) total
FROM farmacias_bienestar
"));

/* =====================================
   TOP MEDICAMENTO
===================================== */

$topMedicamento = pg_fetch_assoc(
pg_query($conn,"
SELECT medicamento,
COUNT(*) total
FROM farmacias_bienestar
GROUP BY medicamento
ORDER BY total DESC
LIMIT 1
"));

if(!$topMedicamento){

    $topMedicamento = [
        'medicamento' => 'Sin datos',
        'total' => 0
    ];
}

/* =====================================
   TOP LOCALIDAD
===================================== */

$topLocalidad = pg_fetch_assoc(
pg_query($conn,"
SELECT localidad,
COUNT(*) total
FROM farmacias_bienestar
GROUP BY localidad
ORDER BY total DESC
LIMIT 1
"));

if(!$topLocalidad){

    $topLocalidad = [
        'localidad' => 'Sin datos',
        'total' => 0
    ];
}

/* =====================================
   HALLAZGO AUTOMÁTICO
===================================== */

$hallazgo = "
La localidad con mayor demanda es
".$topLocalidad['localidad']."
con ".$topLocalidad['total']."
 solicitudes registradas.
";

/* =====================================
   ALERTAS DE ESCASEZ
===================================== */

$medicamentosEscasos = pg_query(
$conn,"
SELECT medicamento,
COUNT(*) cantidad
FROM farmacias_bienestar
GROUP BY medicamento
HAVING COUNT(*) < 20
ORDER BY cantidad ASC
");

$totalEscasos = pg_num_rows(
$medicamentosEscasos
);

/* =====================================
   NIVEL DE RIESGO
===================================== */

if($totalEscasos <= 5){

    $nivelRiesgo = "BAJO";
    $colorRiesgo = "success";

}elseif($totalEscasos <= 15){

    $nivelRiesgo = "MEDIO";
    $colorRiesgo = "warning";

}else{

    $nivelRiesgo = "ALTO";
    $colorRiesgo = "danger";
}

/* =====================================
   DATOS PARA GRAFICA LOCALIDADES
===================================== */

$grafLocalidades = pg_query(
$conn,"
SELECT localidad,
COUNT(*) total
FROM farmacias_bienestar
GROUP BY localidad
ORDER BY total DESC
LIMIT 10
");

$labelsLocalidades = [];
$datosLocalidades = [];

while($fila = pg_fetch_assoc($grafLocalidades)){

    $labelsLocalidades[] =
    $fila['localidad'];

    $datosLocalidades[] =
    $fila['total'];
}

$jsonLocalidades =
json_encode($labelsLocalidades);

$jsonDatosLocalidades =
json_encode($datosLocalidades);

/* =====================================
   DATOS PARA GRAFICA MEDICAMENTOS
===================================== */

$grafMedicamentos = pg_query(
$conn,"
SELECT medicamento,
COUNT(*) total
FROM farmacias_bienestar
GROUP BY medicamento
ORDER BY total DESC
LIMIT 10
");

$labelsMedicamentos = [];
$datosMedicamentos = [];

while($fila = pg_fetch_assoc($grafMedicamentos)){

    $labelsMedicamentos[] =
    $fila['medicamento'];

    $datosMedicamentos[] =
    $fila['total'];
}

$jsonMedicamentos =
json_encode($labelsMedicamentos);

$jsonDatosMedicamentos =
json_encode($datosMedicamentos);

/* =====================================
   COMBOS DE FILTRO
===================================== */

$listaLocalidades = pg_query(
$conn,"
SELECT DISTINCT localidad
FROM farmacias_bienestar
ORDER BY localidad
");

$listaMedicamentos = pg_query(
$conn,"
SELECT DISTINCT medicamento
FROM farmacias_bienestar
ORDER BY medicamento
");

/* =====================================
   FECHA ACTUAL
===================================== */

date_default_timezone_set(
'America/Mexico_City'
);

$fechaActual =
date('d/m/Y');

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>
Análisis y Visualización de Datos de Pacientes
</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

/* MENU */

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

margin-bottom:10px;

background:#ffffff20;

color:white;

text-decoration:none;

border-radius:10px;

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

/* CONTENIDO */

.contenedor{

padding:30px;
padding-top:65px;

}

.banner{

background:linear-gradient(
135deg,
#611232,
#8a1538
);

color:white;

padding:30px;

border-radius:20px;

margin-bottom:25px;

box-shadow:0 5px 20px rgba(0,0,0,.15);

}

.banner h1{

font-weight:700;

}

/* KPI */

.kpi{

background:white;

padding:25px;

border-radius:18px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

transition:.3s;

height:100%;

}

.kpi:hover{

transform:translateY(-5px);

}

.kpi .icono{

font-size:28px;
color:var(--guinda);

}

.kpi h2{

color:var(--guinda);
font-weight:700;

}

/* ALERTAS */

.alerta-box{

background:white;

padding:25px;

border-radius:18px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

}

/* FILTROS */

.filtros{

background:white;

padding:25px;

border-radius:18px;

margin-top:25px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

}

/* TABLA */

.tabla-box{

background:white;

padding:25px;

border-radius:18px;

margin-top:25px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

}

.badge-medicamento{

background:#198754;

color:white;

padding:6px 12px;

border-radius:20px;

}

/* HALLAZGO */

.hallazgo{

background:#fff;

padding:25px;

margin-top:25px;

border-radius:18px;

box-shadow:0 5px 20px rgba(0,0,0,.08);

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

<div class="sidebar" id="sidebar">

<h2>BIENESTAR</h2>

<a href="../dashboard.php">
Dashboard
</a>

<a href="index.php">
Pacientes
</a>

<a href="../medicamentos/index.php">
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

<!-- BANNER -->

<div class="banner">

<h1>
Análisis y Visualización de Datos de Pacientes
</h1>

<p>

Módulo analítico para monitorear demanda,
medicamentos y distribución geográfica.

</p>

<p>

Fecha de consulta:
<strong><?=$fechaActual?></strong>

</p>

</div>

<!-- KPIS -->

<div class="row g-4">

<div class="col-lg-3">

<div class="kpi">

<div class="icono">
<i class="fa-solid fa-users"></i>
</div>

<h5>Pacientes</h5>

<h2>
<?=$totalPacientes['total']?>
</h2>

</div>

</div>

<div class="col-lg-3">

<div class="kpi">

<div class="icono">
<i class="fa-solid fa-capsules"></i>
</div>

<h5>Medicamentos</h5>

<h2>
<?=$totalMedicamentos['total']?>
</h2>

</div>

</div>

<div class="col-lg-3">

<div class="kpi">

<div class="icono">
<i class="fa-solid fa-location-dot"></i>
</div>

<h5>Localidades</h5>

<h2>
<?=$totalLocalidades['total']?>
</h2>

</div>

</div>

<div class="col-lg-3">

<div class="kpi">

<div class="icono">
<i class="fa-solid fa-map"></i>
</div>

<h5>Municipios</h5>

<h2>
<?=$totalMunicipios['total']?>
</h2>

</div>

</div>

</div>

<!-- ALERTAS -->

<div class="row mt-4">

<div class="col-lg-6">

<div class="alerta-box">

<h4>
Centro de Alertas
</h4>

<div class="alert alert-warning">

Medicamentos escasos detectados:

<strong>
<?=$totalEscasos?>
</strong>

</div>

<div class="alert alert-info">

Localidad con mayor demanda:

<strong>
<?=$topLocalidad['localidad']?>
</strong>

</div>

<div class="alert alert-danger">

Nivel de Riesgo:

<strong>
<?=$nivelRiesgo?>
</strong>

</div>

</div>

</div>

<div class="col-lg-6">

<div class="alerta-box">

<h4>
📈 Indicadores Clave
</h4>

<p>

Medicamento más solicitado:

<strong>
<?=$topMedicamento['medicamento']?>
</strong>

</p>

<p>

Total solicitudes:

<strong>
<?=$topMedicamento['total']?>
</strong>

</p>

<p>

Estado del sistema:

<span class="badge bg-<?=$colorRiesgo?>">
<?=$nivelRiesgo?>
</span>

</p>

</div>

</div>

</div>

<!-- FILTROS -->

<div class="filtros">

<form method="GET">

<div class="row">

<div class="col-lg-4">

<input
type="text"
name="buscar"
class="form-control"
placeholder="Buscar paciente..."
value="<?=htmlspecialchars($buscar)?>">

</div>

<div class="col-lg-3">

<select
name="localidad"
class="form-select">

<option value="">
Todas las localidades
</option>

<?php while($loc=pg_fetch_assoc($listaLocalidades)){ ?>

<option
value="<?=$loc['localidad']?>">

<?=$loc['localidad']?>

</option>

<?php } ?>

</select>

</div>

<div class="col-lg-3">

<select
name="medicamento"
class="form-select">

<option value="">
Todos los medicamentos
</option>

<?php while($med=pg_fetch_assoc($listaMedicamentos)){ ?>

<option
value="<?=$med['medicamento']?>">

<?=$med['medicamento']?>

</option>

<?php } ?>

</select>

</div>

<div class="col-lg-2 d-grid">

<button class="btn btn-danger">

Buscar

</button>

</div>

</div>

</form>

</div>

<!-- TABLA -->

<div class="tabla-box">

<h4 class="mb-3">

Pacientes Registrados

</h4>

<table
id="tablaPacientes"
class="table table-striped table-hover">

<thead>

<tr>

<th>ID</th>
<th>Paciente</th>
<th>Teléfono</th>
<th>Medicamento</th>
<th>Localidad</th>
<th>Municipio</th>

</tr>

</thead>

<tbody>

<?php while($row=pg_fetch_assoc($result)){ ?>

<tr>

<td><?=$row['id']?></td>

<td>

<?=htmlspecialchars(

$row['apellido_paterno_derechohabiente']." ".
$row['apellido_materno_derechohabiente']." ".
$row['nombres_derechohabiente']

)?>

</td>

<td>

<?=htmlspecialchars($row['telefono'])?>

</td>

<td>

<span class="badge-medicamento">

<?=htmlspecialchars($row['medicamento'])?>

</span>

</td>

<td>

<?=htmlspecialchars($row['localidad'])?>

</td>

<td>

<?=htmlspecialchars($row['municipio'])?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<!-- HALLAZGO -->

<div class="hallazgo">

<h4>
🤖 Hallazgo Automático
</h4>

<hr>

<p>

<?=$hallazgo?>

</p>

</div>

<!-- TABLA -->

<div class="card shadow border-0">

    <div class="card-header bg-white">

        <h5 class="mb-0">

            <i class="fa-solid fa-table text-primary"></i>
            Base de Datos de Pacientes

        </h5>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table
            id="tablaPacientes"
            class="table table-hover align-middle">

                <thead class="table-dark">

                    <tr>

                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Teléfono</th>
                        <th>Medicamento</th>
                        <th>Localidad</th>
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

                            <strong>

                            <?=htmlspecialchars(

                            $row['apellido_paterno_derechohabiente']." ".
                            $row['apellido_materno_derechohabiente']." ".
                            $row['nombres_derechohabiente']

                            )?>

                            </strong>

                        </td>

                        <td>

                            <span class="text-primary fw-bold">

                                <?=$row['telefono']?>

                            </span>

                        </td>

                        <td>

                            <span class="badge bg-success">

                                <?=$row['medicamento']?>

                            </span>

                        </td>

                        <td>

                            <?=$row['localidad']?>

                        </td>

                        <td>

                            <button
                            class="btn btn-sm btn-info"
                            data-bs-toggle="modal"
                            data-bs-target="#modal<?=$row['id']?>">

                                <i class="fa fa-eye"></i>

                            </button>

                            <a
                            href="editar.php?id=<?=$row['id']?>"
                            class="btn btn-sm btn-warning">

                                <i class="fa fa-pen"></i>

                            </a>

                            <a
                            href="eliminar.php?id=<?=$row['id']?>"
                            onclick="return confirm('¿Eliminar registro?')"
                            class="btn btn-sm btn-danger">

                                <i class="fa fa-trash"></i>

                            </a>

                        </td>

                    </tr>



                    <!-- MODAL DETALLE -->

                    <div
                    class="modal fade"
                    id="modal<?=$row['id']?>">

                        <div class="modal-dialog modal-lg">

                            <div class="modal-content">

                                <div class="modal-header bg-primary text-white">

                                    <h5>

                                        Información Completa

                                    </h5>

                                    <button
                                    class="btn-close btn-close-white"
                                    data-bs-dismiss="modal">
                                    </button>

                                </div>

                                <div class="modal-body">

                                    <div class="row">

                                        <div class="col-md-6">

                                            <strong>Paciente:</strong>

                                            <br>

                                            <?=htmlspecialchars(

                                            $row['apellido_paterno_derechohabiente']." ".
                                            $row['apellido_materno_derechohabiente']." ".
                                            $row['nombres_derechohabiente']

                                            )?>

                                        </div>

                                        <div class="col-md-6">

                                            <strong>Teléfono:</strong>

                                            <br>

                                            <?=$row['telefono']?>

                                        </div>

                                    </div>

                                    <hr>

                                    <div class="row">

                                        <div class="col-md-6">

                                            <strong>Medicamento:</strong>

                                            <br>

                                            <?=$row['medicamento']?>

                                        </div>

                                        <div class="col-md-6">

                                            <strong>Localidad:</strong>

                                            <br>

                                            <?=$row['localidad']?>

                                        </div>

                                    </div>

                                    <hr>

                                    <strong>Domicilio:</strong>

                                    <br>

                                    <?=$row['domicilio']?>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</div>



<footer class="text-center py-4 text-muted">

Sistema Inteligente de Análisis y Visualización de Datos
<br>
Proyecto de Titulación

</footer>



<script>

function abrirMenu(){

    document
    .getElementById("sidebar")
    .classList.add("activo");

    document
    .getElementById("fondo")
    .classList.add("activo");

}

function cerrarMenu(){

    document
    .getElementById("sidebar")
    .classList.remove("activo");

    document
    .getElementById("fondo")
    .classList.remove("activo");

}

</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>

$(document).ready(function(){

    $('#tablaPacientes').DataTable({

        language:{

            url:'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'

        },

        pageLength:10,

        responsive:true

    });

});

</script>

</body>
</html>
