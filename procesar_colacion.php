<?php

// Conexión a la base de datos MySQL
$conexionMySQL = new mysqli("10.10.0.129", "root", "", "casino_prize");
if ($conexionMySQL->connect_error) {
    die("Error de conexión MySQL: " . $conexionMySQL->connect_error);
}

include('conexion_qbiz.php');

$rut = isset($_POST['rut']) ? $_POST['rut'] : ''; 
// Verificar si ya está registrado hoy en MySQL
$hoy = date('Y-m-d');
$query = "SELECT * FROM registros WHERE rut = '$rut' AND fecha_corta = '$hoy'";
$result = $conexionMySQL->query($query);

if ($result->num_rows > 0) {
    // Ya está registrado hoy, no imprimir nada
    echo "Ya se ha registrado la colación para este RUT hoy.";
} else {

    //buscar si es hipocalorico
    // Eliminar puntos y guiones si los hubiera
    $rut_sin = preg_replace('/[^k0-9]/i', '', $rut);
    $dv = substr($rut_sin, -1); // Dígito verificador
    $numero = substr($rut_sin, 0, -1); // Número sin el dígito verificador


        // Asegurarse de que $numero sea numérico antes de formatear
        if (is_numeric($numero)) {
            // Convertir $numero a int antes de usar number_format
            $numero_formateado = number_format((int)$numero, 0, '', '.');
        } else {
            // Manejar el caso donde $numero no es un número
            $numero_formateado = '0'; // O asignar un valor adecuado
        }

        // Devolver el RUT formateado con puntos y guion
        $rut_consulta = $numero_formateado . '-' . strtoupper($dv);

    $sql_hipocalorico = "SELECT * FROM usuarios_colacion WHERE rut = '$rut_consulta'";
    $result_hipo = $conexionMySQL->query($sql_hipocalorico);

    if($result_hipo->num_rows > 0){

        $colacion='HIPOCALORICO';

    }else
    {
        $colacion='NORMAL';
        
    }


    // No está registrado, buscar datos en la base de datos de SQL Server
    $queryTrabajador = "SELECT TOP (1) Empresa_RazonSocial, Entidad, Rut, NombreCompleto, CentroCosto, Cargo, CargoDesc, Area, Vigencia 
                        FROM QBIZ.QBIZ_Funcionarios_VW 
                        WHERE rut = ? AND Vigencia = 'S' 
                        ORDER BY Entidad DESC";

    $consulta = $base_de_datos->prepare($queryTrabajador);
    $consulta->execute([$rut]);
    $rs = $consulta->fetchObject();

    if ($rs != NULL) {
        $nombre = $rs->NombreCompleto;

        $nombre_completo = $rs->NombreCompleto;
        $nombre = explode(" ", $nombre_completo);
        if (count($nombre) == 2) {
            $nombre1 = $nombre[1];
            $apellido = $nombre[0];
        } //solo nombre y dos apellidos
        else if (count($nombre) >2) {
            $nombre1 = $nombre[2];
            $apellido = $nombre[0].' '.$nombre[1];
        }

        $empresa = $rs->Empresa_RazonSocial;
        $area = $rs->Area;
        $fecha = date('d/m/Y H:i:s');
      //  $colacion = 'HIPOCALORICO';  // Puedes modificarlo según tu lógica

// Generar el ticket
$rutaArchivo = "C:/wamp64/www/SistemaCasino/recibo.txt";
$contenido = "\n";
$contenido .= "#**********************Ticket Colacion*******************#\n";
$contenido .= "                                    Prize 2024              \n";
$contenido .= "**************************************************************\n";
$contenido .= "NOMBRE  : $nombre1\n";
$contenido .= "APELLIDO: $apellido\n";
$contenido .= "RUT           : $rut\n";
$contenido .= "EMPRESA: $empresa\n";
$contenido .= "AREA         : $area\n";
$contenido .= "FECHA       : $fecha\n";
$contenido .= "COLACION: $colacion\n\n";

// Opcional: agregar un salto de línea al final
$contenido .= "\n";


        // Escribir el contenido al archivo temporal
        file_put_contents($rutaArchivo, $contenido); // Esto sobrescribirá el archivo cada vez

        // Imprimir el archivo
        exec('powershell.exe -ExecutionPolicy Bypass -File C:/wamp64/www/SistemaCasino2/imprimir.ps1');

        // Registrar la colación en MySQL
       // $insertQuery = "INSERT INTO registros (rut, fecha) VALUES ('$rut', '$hoy')";
        //$conexionMySQL->query($insertQuery);

        echo "Ticket impreso correctamente.";
    } else {

        

        echo "El trabajador con RUT $rut no se encontró.";
    }
}

// Cerrar conexiones
$conexionMySQL->close();
$base_de_datos = null;  // Cierra la conexión a SQL Server
?>