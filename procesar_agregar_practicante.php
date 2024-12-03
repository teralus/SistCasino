<?php
include('conexion.php'); // Conexión a la base de datos MySQL
$conexionMySQL = getDBConnection();

if (!$conexionMySQL) {
    die("Error de conexión a MySQL.");
}

// Obtener los datos del formulario
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$apellido = isset($_POST['apellido']) ? $_POST['apellido'] : '';
$rut = isset($_POST['rut']) ? $_POST['rut'] : '';
$empresa = isset($_POST['empresa']) ? $_POST['empresa'] : '';
$fecha_creacion = date('Y-m-d'); // Fecha de creación
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '';
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '';
$cargo = isset($_POST['cargo']) ? $_POST['cargo'] : '';
$area = isset($_POST['area']) ? $_POST['area'] : '';

// Insertar el nuevo practicante
$query = $conexionMySQL->prepare("INSERT INTO practicantes (nombre, apellido, rut, empresa, fecha_creacion, fecha_desde, fecha_hasta, cargo, area) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$query->bind_param("sssssssss", $nombre, $apellido, $rut, $empresa, $fecha_creacion, $fecha_desde, $fecha_hasta, $cargo, $area);

if ($query->execute()) {
    echo "<script>alert('Practicante agregado con éxito.'); window.location.href = 'http://localhost/SistemaCasino2/agregar_practicante.php';</script>";
} else {
    echo "<script>alert('Error al agregar practicante: " . $conexionMySQL->error . "'); window.location.href = 'http://localhost/SistemaCasino2/agregar_practicante.php';</script>";
}

$conexionMySQL->close();
?>
