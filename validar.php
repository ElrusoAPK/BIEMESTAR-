<?php
session_start();

include '../config/conexion.php';

$usuario = $_POST['usuario'];
$password = $_POST['password'];

$query = "SELECT * FROM usuarios WHERE usuario=$1";
$result = pg_query_params($conn, $query, array($usuario));

$user = pg_fetch_assoc($result);

if($user){

    if($password == $user['password']){

        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];

        header("Location: ../dashboard.html");

    }else{

        echo "Contraseña incorrecta";

    }

}else{

    echo "Usuario no encontrado";

}
?>