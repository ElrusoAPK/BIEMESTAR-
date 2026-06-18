<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Agregar Medicamento</title>

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

display:flex;
justify-content:center;
align-items:center;

min-height:100vh;

padding:20px;
}

.form-box{

width:100%;
max-width:700px;

background:white;

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

transition:.3s;

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

cursor:pointer;

font-size:16px;

font-weight:bold;

text-decoration:none;

text-align:center;

transition:.3s;

}

.btn-guardar{

background:#611232;

color:white;

}

.btn-guardar:hover{

background:#7b1a40;

}

.btn-cancelar{

background:#6c757d;

color:white;

}

.btn-cancelar:hover{

background:#555;

}

.icono{

margin-right:8px;

}

</style>

</head>

<body>

<div class="form-box">

<h1>

<i class="fa-solid fa-capsules"></i>

Agregar Medicamento

</h1>

<form action="guardar.html" method="POST">

<div class="form-group">

<label>

<i class="fa-solid fa-pills icono"></i>

Medicamento

</label>

<input
type="text"
name="medicamento"
required>

</div>

<div class="form-group">

<label>

<i class="fa-solid fa-boxes-stacked icono"></i>

Cantidad Surtida

</label>

<input
type="number"
name="surtida"
required>

</div>

<div class="form-group">

<label>

<i class="fa-solid fa-barcode icono"></i>

Lote

</label>

<input
type="text"
name="lote"
required>

</div>

<div class="form-group">

<label>

<i class="fa-solid fa-calendar-days icono"></i>

Fecha de Caducidad

</label>

<input
type="date"
name="fecha_caducidad"
required>

</div>

<div class="botones">

<button
type="submit"
class="btn btn-guardar">

<i class="fa-solid fa-floppy-disk"></i>

Guardar

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