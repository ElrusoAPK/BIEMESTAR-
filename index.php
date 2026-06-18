<?php

session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: ../index.php");
    exit();
}

include '../config/conexion.php';

date_default_timezone_set('America/Mexico_City');

/* =====================================
   FECHA ACTUAL
===================================== */

$fechaActual = date("d/m/Y H:i:s");

/* =====================================
   TOTALES GENERALES
===================================== */

$totalRegistros = pg_fetch_assoc(
pg_query(
$conn,
"SELECT COUNT(*) total FROM farmacias_bienestar"
)
);

$totalMedicamentos = pg_fetch_assoc(
pg_query(
$conn,
"SELECT COUNT(DISTINCT medicamento) total
FROM farmacias_bienestar"
)
);

$totalLocalidades = pg_fetch_assoc(
pg_query(
$conn,
"SELECT COUNT(DISTINCT localidad) total
FROM farmacias_bienestar"
)
);

$totalMunicipios = pg_fetch_assoc(
pg_query(
$conn,
"SELECT COUNT(DISTINCT municipio) total
FROM farmacias_bienestar"
)
);

/* =====================================
   STOCK BAJO
===================================== */

$stockBajo = pg_fetch_assoc(
pg_query(
$conn,
"
SELECT COUNT(*) total
FROM farmacias_bienestar
WHERE CAST(
COALESCE(NULLIF(surtida,''),'0')
AS INTEGER) < 10
"
)
);

/* =====================================
   AGOTADOS
===================================== */

$agotados = pg_fetch_assoc(
pg_query(
$conn,
"
SELECT COUNT(*) total
FROM farmacias_bienestar
WHERE CAST(
COALESCE(NULLIF(surtida,''),'0')
AS INTEGER)=0
"
)
);

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

")

);

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

")

);

/* =====================================
   INDICE DE ABASTECIMIENTO
===================================== */

$indiceAbastecimiento =

$totalMedicamentos['total'] > 0

?

round(
(
($totalMedicamentos['total']
-
$stockBajo['total']
)
/
$totalMedicamentos['total']
)
*100
)

:0;

/* =====================================
   NIVEL DE RIESGO
===================================== */

if($indiceAbastecimiento >= 85){

$nivelRiesgo = "BAJO";
$colorRiesgo = "success";

}elseif($indiceAbastecimiento >= 60){

$nivelRiesgo = "MEDIO";
$colorRiesgo = "warning";

}else{

$nivelRiesgo = "ALTO";
$colorRiesgo = "danger";

}

/* =====================================
   TOP MEDICAMENTOS GRAFICA
===================================== */

$grafMedicamentos = pg_query($conn,"

SELECT medicamento,
COUNT(*) total

FROM farmacias_bienestar

GROUP BY medicamento

ORDER BY total DESC

LIMIT 10

");

/* =====================================
   TOP LOCALIDADES
===================================== */

$grafLocalidades = pg_query($conn,"

SELECT localidad,
COUNT(*) total

FROM farmacias_bienestar

GROUP BY localidad

ORDER BY total DESC

LIMIT 10

");

/* =====================================
   TOP MUNICIPIOS
===================================== */

$grafMunicipios = pg_query($conn,"

SELECT municipio,
COUNT(*) total

FROM farmacias_bienestar

GROUP BY municipio

ORDER BY total DESC

LIMIT 10

");

/* =====================================
   INVENTARIO
===================================== */

$inventario = pg_query($conn,"

SELECT

medicamento,

SUM(
COALESCE(
CAST(
NULLIF(surtida,'')
AS INTEGER
),0
)
) AS stock

FROM farmacias_bienestar

GROUP BY medicamento

ORDER BY stock DESC

");

/* =====================================
   ARRAYS PARA CHART.JS
===================================== */

$labelsMedicamentos = [];
$datosMedicamentos = [];

while($row = pg_fetch_assoc($grafMedicamentos)){

$labelsMedicamentos[] = $row['medicamento'];
$datosMedicamentos[] = $row['total'];

}

$labelsLocalidades = [];
$datosLocalidades = [];

while($row = pg_fetch_assoc($grafLocalidades)){

$labelsLocalidades[] = $row['localidad'];
$datosLocalidades[] = $row['total'];

}

$labelsMunicipios = [];
$datosMunicipios = [];

while($row = pg_fetch_assoc($grafMunicipios)){

$labelsMunicipios[] = $row['municipio'];
$datosMunicipios[] = $row['total'];

}

$jsonMedicamentos =
json_encode($labelsMedicamentos);

$jsonDatosMedicamentos =
json_encode($datosMedicamentos);

$jsonLocalidades =
json_encode($labelsLocalidades);

$jsonDatosLocalidades =
json_encode($datosLocalidades);

$jsonMunicipios =
json_encode($labelsMunicipios);

$jsonDatosMunicipios =
json_encode($datosMunicipios);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>
Centro de Inteligencia de Medicamentos
</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

:root{
    --guinda:#611232;
    --guinda2:#8a1538;
    --gris:#f4f6f9;
    --blanco:#ffffff;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--gris);
    font-family:'Segoe UI',sans-serif;
    color:#333;
}
* ==============================
   MENU
============================== */

.menu-btn{

    position:fixed;
    top:12px;
    right:15px;

    width:38px;
    height:38px;

    border:none;
    border-radius:10px;

    background:var(--guinda);
    color:white;

    z-index:99999;

    box-shadow:0 4px 15px rgba(0,0,0,.25);

}

.sidebar{

    position:fixed;

    top:0;
    right:-270px;

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
    margin-bottom:30px;

}

.sidebar a{

    display:block;

    padding:12px;

    margin-bottom:12px;

    border-radius:10px;

    background:#ffffff20;

    color:white;

    text-decoration:none;

    transition:.3s;

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

/* ===========================
   BANNER
=========================== */

.banner{
    background:linear-gradient(
        135deg,
        var(--guinda),
        var(--guinda2)
    );
    color:white;
    padding:30px;
    border-radius:20px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.15);
}

.banner h1{
    font-size:32px;
    font-weight:700;
    margin-bottom:10px;
}

.banner p{
    opacity:.95;
    margin-bottom:0;
}

/* ===========================
   KPI CARDS
=========================== */

.card-kpi{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    text-align:center;
    transition:.3s;
    height:100%;
}

.card-kpi:hover{
    transform:translateY(-5px);
}

.card-kpi h6{
    color:#666;
    margin-bottom:10px;
}

.card-kpi h2{
    color:var(--guinda);
    font-size:2.2rem;
    font-weight:700;
    margin-bottom:5px;
}

.card-kpi small{
    color:#888;
}

/* ===========================
   ALERTAS
=========================== */

.alertas-box{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    height:100%;
}

.alertas-box h4{
    color:var(--guinda);
    margin-bottom:15px;
}

/* ===========================
   GRAFICAS
=========================== */

.chart-box{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.chart-box h5{
    color:var(--guinda);
    margin-bottom:20px;
}

.chart-box canvas{
    max-height:400px;
}

/* ===========================
   TABLAS
=========================== */

.table-responsive{
    overflow:auto;
}

.table{
    margin-bottom:0;
}

.table thead th{
    background:var(--guinda);
    color:white;
    border:none;
}

.table-hover tbody tr:hover{
    background:#f8f9fa;
}

/* ===========================
   BADGES
=========================== */

.badge{
    padding:8px 12px;
    font-size:.85rem;
}

/* ===========================
   PROGRESS
=========================== */

.progress{
    height:25px;
    border-radius:15px;
}

.progress-bar{
    font-weight:600;
}

/* ===========================
   LIST GROUP
=========================== */

.list-group-item{
    padding:15px;
}

/* ===========================
   FOOTER
=========================== */

footer{
    font-size:14px;
    color:#666;
}

/* ===========================
   RESPONSIVE
=========================== */

@media(max-width:768px){

    .banner h1{
        font-size:24px;
    }

    .card-kpi h2{
        font-size:1.8rem;
    }

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
    <!-- =====================================
         ENCABEZADO EJECUTIVO
    ===================================== -->

    <div class="banner">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h1>
                    <i class="fa-solid fa-chart-line"></i>
                    Centro de Inteligencia de Medicamentos
                </h1>

                <p>

                    Sistema de análisis, monitoreo y visualización
                    de datos para la gestión estratégica de
                    medicamentos en Farmacias Bienestar.

                </p>

            </div>

            <div class="col-lg-4 text-end">

                <h5>
                    Fecha de actualización
                </h5>

                <h4>
                    <?= $fechaActual ?>
                </h4>

            </div>

        </div>

    </div>

    <!-- =====================================
         KPIs
    ===================================== -->

    <div class="row g-4">

        <div class="col-lg-3 col-md-6">

            <div class="card-kpi">

                <h6>
                    Total Registros
                </h6>

                <h2>
                    <?= number_format($totalRegistros['total']) ?>
                </h2>

                <small>
                    Registros procesados
                </small>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card-kpi">

                <h6>
                    Medicamentos
                </h6>

                <h2>
                    <?= number_format($totalMedicamentos['total']) ?>
                </h2>

                <small>
                    Catálogo disponible
                </small>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card-kpi">

                <h6>
                    Localidades
                </h6>

                <h2>
                    <?= number_format($totalLocalidades['total']) ?>
                </h2>

                <small>
                    Cobertura territorial
                </small>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card-kpi">

                <h6>
                    Municipios
                </h6>

                <h2>
                    <?= number_format($totalMunicipios['total']) ?>
                </h2>

                <small>
                    Municipios atendidos
                </small>

            </div>

        </div>

    </div>

    <!-- =====================================
         ALERTAS Y PANEL EJECUTIVO
    ===================================== -->

    <div class="row mt-4">

        <div class="col-lg-5">

            <div class="alertas-box">

                <h4 class="mb-3 text-danger">
                    Centro de Alertas
                </h4>

                <div class="alert alert-danger">

                    Medicamentos agotados:

                    <strong>
                        <?= $agotados['total'] ?>
                    </strong>

                </div>

                <div class="alert alert-warning">

                    Medicamentos con stock bajo:

                    <strong>
                        <?= $stockBajo['total'] ?>
                    </strong>

                </div>

                <div class="alert alert-info">

                    Localidad con mayor demanda:

                    <strong>
                        <?= htmlspecialchars($topLocalidad['localidad']) ?>
                    </strong>

                </div>

            </div>

        </div>

        <div class="col-lg-7">

            <div class="alertas-box">

                <h4 class="mb-3">
                    Panel Ejecutivo
                </h4>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <div class="border rounded p-3">

                            <strong>
                                Medicamento más solicitado
                            </strong>

                            <br>

                            <?= htmlspecialchars($topMedicamento['medicamento']) ?>

                            <br>

                            <small class="text-muted">

                                <?= $topMedicamento['total'] ?>
                                registros

                            </small>

                        </div>

                    </div>

                    <div class="col-md-6 mb-3">

                        <div class="border rounded p-3">

                            <strong>
                                Nivel de Riesgo
                            </strong>

                            <br><br>

                            <span class="badge bg-<?= $colorRiesgo ?> fs-6">

                                <?= $nivelRiesgo ?>

                            </span>

                        </div>

                    </div>

                    <div class="col-md-12">

                        <div class="border rounded p-3">

                            <strong>
                                Índice de Abastecimiento
                            </strong>

                            <div class="progress mt-3">

                                <div
                                    class="progress-bar bg-success"
                                    role="progressbar"
                                    style="width: <?= $indiceAbastecimiento ?>%;">

                                    <?= $indiceAbastecimiento ?>%

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- =====================================
         GRAFICAS
    ===================================== -->

    <div class="row mt-4">

        <div class="col-lg-6">

            <div class="chart-box">

                <h5>
                    Top 10 Medicamentos
                </h5>

                <canvas id="grafMedicamentos"></canvas>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="chart-box">

                <h5>
                    Top 10 Localidades
                </h5>

                <canvas id="grafLocalidades"></canvas>

            </div>

        </div>

    </div>

    <div class="row mt-4">

        <div class="col-lg-12">

            <div class="chart-box">

                <h5>
                    Municipios con Mayor Demanda
                </h5>

                <canvas id="grafMunicipios"></canvas>

            </div>

        </div>

    </div>
        <!-- =====================================
         INVENTARIO GENERAL
    ===================================== -->

    <div class="row mt-4">

        <div class="col-12">

            <div class="chart-box">

                <h4 class="mb-3">
                    Inventario General de Medicamentos
                </h4>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>Medicamento</th>
                                <th>Stock</th>
                                <th>Estado</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php
                        pg_result_seek($inventario,0);

                        while($row = pg_fetch_assoc($inventario)){

                            $stock = (int)$row['stock'];

                            if($stock == 0){

                                $estado = "Agotado";
                                $clase = "danger";

                            }elseif($stock < 10){

                                $estado = "Stock Bajo";
                                $clase = "warning";

                            }else{

                                $estado = "Disponible";
                                $clase = "success";

                            }
                        ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row['medicamento']) ?>
                                </td>

                                <td>
                                    <?= $stock ?>
                                </td>

                                <td>

                                    <span class="badge bg-<?= $clase ?>">

                                        <?= $estado ?>

                                    </span>

                                </td>

                            </tr>

                        <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <!-- =====================================
         HALLAZGOS AUTOMÁTICOS
    ===================================== -->

    <div class="row mt-4">

        <div class="col-12">

            <div class="alertas-box">

                <h4 class="mb-3">
                    🤖 Hallazgos Automáticos del Sistema
                </h4>

                <ul class="list-group">

                    <li class="list-group-item">

                        El medicamento más solicitado es:

                        <strong>
                            <?= htmlspecialchars($topMedicamento['medicamento']) ?>
                        </strong>

                        con

                        <strong>
                            <?= $topMedicamento['total'] ?>
                        </strong>

                        registros.

                    </li>

                    <li class="list-group-item">

                        La localidad con mayor demanda es:

                        <strong>
                            <?= htmlspecialchars($topLocalidad['localidad']) ?>
                        </strong>

                        con

                        <strong>
                            <?= $topLocalidad['total'] ?>
                        </strong>

                        solicitudes.

                    </li>

                    <li class="list-group-item">

                        Se detectaron

                        <strong>
                            <?= $stockBajo['total'] ?>
                        </strong>

                        medicamentos con riesgo de escasez.

                    </li>

                    <li class="list-group-item">

                        Existen

                        <strong>
                            <?= $agotados['total'] ?>
                        </strong>

                        medicamentos agotados.

                    </li>

                    <li class="list-group-item">

                        El índice general de abastecimiento es de

                        <strong>
                            <?= $indiceAbastecimiento ?>%
                        </strong>

                        con nivel de riesgo

                        <span class="badge bg-<?= $colorRiesgo ?>">
                            <?= $nivelRiesgo ?>
                        </span>

                    </li>

                </ul>

            </div>

        </div>

    </div>

    <!-- =====================================
         CONCLUSIÓN EJECUTIVA
    ===================================== -->

    <div class="row mt-4">

        <div class="col-12">

            <div class="alert alert-primary p-4">

                <h4>
                 Resumen Ejecutivo
                </h4>

                <p class="mb-0">

                    El sistema analiza la distribución y disponibilidad
                    de medicamentos dentro de Farmacias Bienestar,
                    permitiendo identificar riesgos de desabasto,
                    zonas con mayor demanda y medicamentos críticos,
                    facilitando la toma de decisiones estratégicas
                    para mejorar el abastecimiento.

                </p>

            </div>

        </div>

    </div>

    <!-- =====================================
         FOOTER
    ===================================== -->

    <footer class="text-center mt-5 mb-3 text-muted">

        Sistema Inteligente de Análisis y Visualización de Datos |
        Farmacias Bienestar |
        Proyecto de Titulación IF

        <br>

        <?= $fechaActual ?>

    </footer>

</div>

<!-- =====================================
     CHART.JS
===================================== -->

<script>

new Chart(
document.getElementById('grafMedicamentos'),
{

type:'bar',

data:{

labels:<?= $jsonMedicamentos ?>,

datasets:[{

label:'Solicitudes',

data:<?= $jsonDatosMedicamentos ?>,

backgroundColor:'#611232'

}]

},

options:{

responsive:true,

plugins:{
legend:{
display:false
}
}

}

});

</script>
    
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

<script>

new Chart(
document.getElementById('grafLocalidades'),
{

type:'doughnut',

data:{

labels:<?= $jsonLocalidades ?>,

datasets:[{

data:<?= $jsonDatosLocalidades ?>

}]

},

options:{

responsive:true

}

});

</script>

<script>

new Chart(
document.getElementById('grafMunicipios'),
{

type:'bar',

data:{

labels:<?= $jsonMunicipios ?>,

datasets:[{

label:'Solicitudes',

data:<?= $jsonDatosMunicipios ?>,

backgroundColor:'#8a1538'

}]

},

options:{

responsive:true,

plugins:{
legend:{
display:false
}
}

}

});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>