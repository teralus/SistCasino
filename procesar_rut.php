<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
function formatearRut($rut)
{
    // Eliminar todos los caracteres que no son dígitos o 'k' o 'K'
    $rut_sin = preg_replace('/[^0-9kK]/', '', $rut);
    $dv = substr($rut_sin, -1); // Dígito verificador
    $numero = substr($rut_sin, 0, -1); // Número sin el dígito verificador

    // Verificar si el número es válido antes de formatear
    if (is_numeric($numero)) {
        // Agregar puntos si es un número válido
        $numero_formateado = number_format($numero, 0, '', '.');
    } else  {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se ha proporcionado un RUT.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'http://localhost/SistemaCasino2/';
                    }
                });
            </script>
        </body>
        </html>";
        exit;
    }
    

    // Retornar el RUT formateado
    return $numero_formateado . '-' . strtoupper($dv);
}

include('conexion.php');
include('conexion_qbiz.php');
//date_default_timezone_set('America/Santiago'); // Establecer la zona horaria de Chile
date_default_timezone_set('America/Santiago');

$conexionMySQL = getDBConnection();
$conexionVisitaMySQL = getVisitasDBConnection();


if (!$conexionMySQL) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error de Conexión</title>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'>
    </head>
    <body>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                title: 'Error',
                text: 'Error de conexión a MySQL.',
                icon: 'error'
            }).then(() => {
                window.location.href = 'http://localhost/SistemaCasino2/';
            });
        </script>
    </body>
    </html>";
    exit;
}

// Obtener el RUT del formulario
$rut = isset($_POST['rut']) ? $_POST['rut'] : '';

if (!$rut) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se ha proporcionado un RUT.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'http://localhost/SistemaCasino2/';
                }
            });
        </script>
    </body>
    </html>";
    exit;
}



// Eliminar puntos y guiones si los hubiera
$rut_sin = preg_replace('/[^0-9kK]/', '', $rut);
$dv = substr($rut_sin, -1); // Dígito verificador
$numero = substr($rut_sin, 0, -1); // Número sin el dígito verificador
$rut_sinpunto = ($numero . '-' . $dv); // RUT sin formato

// Unificar el formato del RUT para consultas (sin puntos, con guion y dígito verificador en mayúscula)
// Unificar el formato del RUT para consultas (sin puntos, con guion y dígito verificador en mayúscula)
$rut_consulta = strtoupper($rut_sinpunto);

// Obtener la fecha actual
$hoy = date('Y-m-d');

// Verificar si ya está registrado hoy en MySQL con el servicio específico
$query = $conexionMySQL->prepare("SELECT * 
    FROM registros 
    WHERE rut = ? AND fecha_corta = ? AND servicio = ?
");
$query->bind_param("sss", $rut_consulta, $hoy, $servicio_actual);

// Determinar el servicio según la hora actual
$hora_actual = date('H:i:s');
$servicio_actual = ($hora_actual <= '17:00:00') ? 'ALMUERZO' : 'CENA';

$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    // Si el servicio ya está registrado para el día actual
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Registro duplicado',
                text: 'Ya se ha registrado el servicio de $servicio_actual para este RUT hoy.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'http://localhost/SistemaCasino2/';
                }
            });
        </script>
    </body>
    </html>";
    exit;
}


// Consulta de tipo de colación en usuarios_colacion
$sql_hipo = $conexionMySQL->prepare("SELECT * FROM usuarios_colacion WHERE rut = ?");
$sql_hipo->bind_param("s", $rut_consulta);
$sql_hipo->execute();
$result_hipo = $sql_hipo->get_result();
$colacion = ($result_hipo->num_rows > 0) ? 'HIPOCALORICO' : 'NORMAL';

// Verificar en QBIZ
$queryTrabajador = "SELECT TOP (1) Empresa_RazonSocial, Entidad, Rut, NombreCompleto, Area, CentroCosto
                    FROM QBIZ.QBIZ_Funcionarios_VW 
                    WHERE Rut = ? AND Vigencia = 'S'
                    ORDER BY Entidad DESC";

$consulta = $base_de_datos->prepare($queryTrabajador);
$consulta->execute([$rut_sinpunto]);
$rs = $consulta->fetchObject();


if ($rs) {
    // Si se encuentra en QBIZ, procesar como trabajador
    procesarTicket($rs, $rut_consulta, $colacion, $conexionMySQL, $hoy);
} else {
    // Buscar como contratista externo en SQL Server
    $queryContratista = "SELECT Empresa, 'CONTRATISTA' AS TipoEntidad, RIGHT(Clasif7, 4) AS Entidad, Texto2 AS Rut, 
                         CASE WHEN LEN(Texto2) = 9 THEN '0' + Texto2 ELSE Texto2 END AS Rut2, 
                         CONCAT(Texto1, ' ', Clasif9, ' ', Clasif10) AS NombreCompleto, Texto1 AS Nombres, 
                         Clasif9 AS ap_paterno, Clasif10 AS ap_materno, Clasif4 AS area,
                         CASE WHEN Fecha10 = '01-01-1900' THEN 'V' ELSE 'N' END AS Vigencia
                    FROM DocumentoInfo WITH (nolock)
                    WHERE empresa = 'Natura' 
                    AND Fecha10 = '01-01-1900'
                    AND TipoDocumento = 'NOMINA MANO DE OBRA EXTERNA V2'
                    AND Correlativo = (SELECT MAX(Correlativo) 
                                       FROM Documento WITH (nolock) 
                                       WHERE empresa = 'Natura' 
                                       AND TipoDocumento = 'NOMINA MANO DE OBRA EXTERNA V2')
                    AND Seccion = 'DETALLE'
                    AND Texto2 = ?";

    $consultaContratista = $base_de_datos->prepare($queryContratista);
    $consultaContratista->execute([$rut_sinpunto]);
    $rsContratista = $consultaContratista->fetchObject();

    if ($rsContratista) {
        // Si se encuentra como contratista
        procesarTicket($rsContratista, $rut_consulta, $colacion, $conexionMySQL, $hoy);
    } else {
        $rut_formateado = formatearRut($rut);

        // Verificar en la tabla de contratistas de MySQL
        $sql_contratista = $conexionMySQL->prepare("SELECT * FROM contratista WHERE rut = ?");
        $sql_contratista->bind_param("s", $rut_formateado);
        $sql_contratista->execute();
        $result_contratista = $sql_contratista->get_result();

        if ($result_contratista->num_rows > 0) {
            $data_contratista = $result_contratista->fetch_object();
            procesarTicket($data_contratista, $rut_consulta, $colacion, $conexionMySQL, $hoy);
        } 
            // Verificar en la tabla practicantes de MySQL
            else {
                // Verificar en la tabla practicantes y realizar un JOIN con contactos para obtener el centrocosto
                $sql_practicantes = $conexionMySQL->prepare("
                    SELECT 
                        p.nombre, 
                        p.apellido, 
                        p.rut, 
                        p.empresa, 
                        p.fecha_desde, 
                        p.fecha_hasta, 
                        p.cargo, 
                        c.area, 
                        cc.centrocosto
                    FROM casino_prize.practicantes p
                    INNER JOIN visitas.contactos c ON p.area = c.area
                    INNER JOIN visitas.contactos cc ON c.id_contacto = cc.id_contacto
                    WHERE p.rut = ?
                ");
                $sql_practicantes->bind_param("s", $rut_consulta);
                $sql_practicantes->execute();
                $result_practicantes = $sql_practicantes->get_result();
            
                if ($result_practicantes->num_rows > 0) {
                    $data_practicantes = $result_practicantes->fetch_object();
                    $fecha_desde = $data_practicantes->fecha_desde;
                    $fecha_hasta = $data_practicantes->fecha_hasta;
                    $centrocosto = $data_practicantes->centrocosto;
            
                    // Verificar si la fecha actual está dentro del rango
                    if ($hoy >= $fecha_desde && $hoy <= $fecha_hasta) {
                        // Aquí pasamos los datos obtenidos, incluyendo el centrocosto, a la función procesarTicket
                        procesarTicket($data_practicantes, $rut_consulta, $colacion, $conexionMySQL, $hoy, $centrocosto);
                    }
                }
            }
          
            }
// Asegúrate de tener las conexiones correctas antes de la consulta
$conexionVisitaMySQL = getVisitasDBConnection();  // Para la tabla 'visitas'
$conexionMySQL = getDBConnection();  // Para la tabla 'contactos'

// Realiza la consulta usando la conexión correcta
// Usando la conexión correcta para la consulta de visitas
$sql_visitas = $conexionVisitaMySQL->prepare("SELECT 
    v.id_contacto, 
    v.nombre, 
    v.apellido, 
    v.rut, 
    c.nombres AS nombre_contacto, 
    c.centrocosto 
FROM casino_prize.visitas v
INNER JOIN visitas.contactos c ON v.id_contacto = c.id_contacto
WHERE v.rut = ?");



// Vincular el parámetro con el valor del RUT a consultar
$sql_visitas->bind_param("s", $rut_consulta);

// Ejecutar la consulta
$sql_visitas->execute();

// Obtener los resultados
$result_visitas = $sql_visitas->get_result();
if ($result_visitas->num_rows > 0) {
    // Procesar la primera fila del resultado
    $data_visita = $result_visitas->fetch_object();

    // Acceder a los datos obtenidos
    $nombre_contacto = $data_visita->nombre_contacto;
    $centrocosto = $data_visita->centrocosto;

    // Llamar a otra función con los valores obtenidos
    procesarTicket($data_visita, $rut_consulta, $colacion, $conexionMySQL, $hoy);
}


}

function procesarTicket($rs, $rut_consulta, $colacion, $conexionMySQL, $hoy)
{

    // Verificar si se trata de un practicante, contratista o trabajador de QBIZ
    if (isset($rs->nombre) && isset($rs->apellido)) {
        // Para practicantes de la tabla 'practicantes'     
        $nombre1 = trim($rs->nombre);
        $apellidos = trim($rs->apellido);
        $centrocosto = isset($rs->centrocosto) ? trim($rs->centrocosto) : "INR260000000";
        $empresa = isset($rs->empresa) ? trim($rs->empresa) : "VISITA";
        $area =  isset($rs->area) ? trim($rs->area) : "VISITA";
    } elseif (isset($rs->nombre) && isset($rs->apellido)) {
        $nombre1 = trim($rs->nombre);
        $apellidos = trim($rs->apellido);
        $empresa = isset($rs->empresa);
        $centrocosto = 'INR260000000';  // Asignar centro de costo para practicantes


    } elseif (isset($rs->Nombres) && isset($rs->ap_paterno) && isset($rs->ap_materno)) {
        // Para contratistas de la tabla 'contratista'
        $nombre1 = trim($rs->Nombres);  // Asegurarse de eliminar espacios extra
        $apellidos = trim($rs->ap_paterno) . ' ' . trim($rs->ap_materno);  // Apellidos combinados

        if (isset($rs->TipoEntidad) && $rs->TipoEntidad === 'CONTRATISTA') {
            $centrocosto =  'INR200000000';
        } else {
            $centrocosto = 'No especificado';
        }
        $empresa = isset($rs->Empresa) ? trim($rs->Empresa) : 'No especificado';
    } elseif (isset($rs->nombres) && isset($rs->ap_paterno) && isset($rs->ap_materno)) {
        // Para contratistas de la tabla 'contratista'
        $nombre1 = trim($rs->nombres);  // Asegurarse de eliminar espacios extra
        $apellidos = trim($rs->ap_paterno) . ' ' . trim($rs->ap_materno);  // Apellidos combinados
        $centrocosto =  'INR200000000';
        $empresa = isset($rs->empresa_contrato) ? trim($rs->empresa_contrato) : 'No especificado';
    } elseif (isset($rs->NombreCompleto)) {
        // Para registros de QBIZ u otra fuente con 'NombreCompleto'
        $nombreCompleto = explode(' ', trim($rs->NombreCompleto));

        // Aseguramos que se maneje correctamente el número de nombres y apellidos
        $numPartes = count($nombreCompleto);
        if ($numPartes >= 4) {
            // Asumimos dos nombres y dos apellidos
            $nombre1 = $nombreCompleto[2] . ' ' . $nombreCompleto[3];  // Primer y segundo nombre
            $apellidos = $nombreCompleto[0] . ' ' . $nombreCompleto[1];  // Primer y segundo apellido
        } elseif ($numPartes == 3) {
            // Un nombre y dos apellidos
            $nombre1 = $nombreCompleto[2];  // Primer nombre
            $apellidos = $nombreCompleto[0] . ' ' . $nombreCompleto[1];  // Dos apellidos
        } elseif ($numPartes == 2) {
            // Un nombre y un apellido
            $nombre1 = $nombreCompleto[1];  // Primer nombre
            $apellidos = $nombreCompleto[0];  // Un apellido
        } else {
            // Si no se puede separar correctamente, tratamos todo como un nombre
            $nombre1 = $rs->NombreCompleto;
            $apellidos = 'Desconocido';
        }
        $empresa = isset($rs->Empresa_RazonSocial) ? trim($rs->Empresa_RazonSocial) : 'No especificado';
        $centrocosto = isset($rs->CentroCosto) ? trim($rs->CentroCosto) : 'No especificado';
    } else {
        $nombre1 = 'Desconocido';
        $apellidos = 'Desconocido';
        $empresa = 'No especificado';
        $centrocosto = 'No especificado';
    }

    // Asignación de otros campos como área
    $area = isset($rs->area) ? trim($rs->area) : (isset($rs->Area) ? trim($rs->Area) : 'VISITA');
    $fecha = date('d/m/Y H:i:s');
    $hoy = date('Y-m-d H:i:s');
    $fecha_corta = date('Y-m-d', strtotime($hoy));

    // Determinar el tipo de servicio según la hora
    $hora_actual = date('H:i:s');
    if ($hora_actual <= '17:00:00') {
        $servicio = 'ALMUERZO';
    } else {
        $servicio = 'CENA';
    }

    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f0f0f0;
                margin: 0;
            }
            .loading-circle {
                border: 8px solid #f3f3f3;
                border-top: 8px solid #ffcc00;
                border-radius: 50%;
                width: 100px; /* Tamaño más grande */
                height: 100px; /* Tamaño más grande */
                animation: spin 1s linear infinite;
                margin: 0 auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <script>
            Swal.fire({
                html: '<div style=\"text-align: center; display: flex; flex-direction: column; align-items: center;\">' +
                        '<div class=\"loading-circle\"></div>' +
                        '<h3>Cargando...</h3>' +
                        '<p>Por favor, espere mientras procesamos el ticket.</p>' +
                        '</div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                width: '500px', /* Ajusta el tamaño del cuadro */
                padding: '40px', /* Agrega padding para que se vea más espacioso */
                onOpen: () => {
                    Swal.showLoading();
                }
            });
        </script>
    </body>
    </html>";


    ob_flush();
    flush();
    sleep(1);


    // Generar el ticket en el archivo 'recibo.txt'
    $rutaArchivo = "C:/wamp64/www/SistemaCasino2/recibo.txt";

    $contenido = "\n#********************* Ticket Colación *********************#\n";
    $contenido .= "                                 Prize 2024                     \n";
    $contenido .= "*****************************************************************\n";
    $contenido .= "NOMBRES  : $nombre1\n";
    $contenido .= "APELLIDOS: $apellidos\n";
    $contenido .= "RUT      : $rut_consulta\n";
    $contenido .= "EMPRESA  : $empresa\n";
    $contenido .= "ÁREA     : $area\n";
    $contenido .= "FECHA    : $fecha\n";
    $contenido .= "COLACIÓN : $colacion\n\n";
    $contenido .= "****************************************************************\n";
    $contenido .= "\n\n"; // Dos líneas en blanco adicionales


    // Escribir el contenido al archivo temporal
    if (file_put_contents($rutaArchivo, $contenido) !== false) {

        // Registrar la colación en MySQL con el nuevo campo 'servicio'
        $insert = $conexionMySQL->prepare("INSERT INTO registros (rut, nombres, apellidos, empresa_contrato, area, tipo_colacion, fecha, fecha_corta, ccosto, servicio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssssssss", $rut_consulta, $nombre1, $apellidos, $empresa, $area, $colacion, $hoy, $fecha_corta, $centrocosto, $servicio);
        if ($insert->execute()) {
            exec('powershell.exe -ExecutionPolicy Bypass -File C:/wamp64/www/SistemaCasino2/imprimir.ps1');

            echo "<script>
            Swal.close(); // Cerrar el loading
            Swal.fire({
                icon: 'success',
                title: 'Ticket generado',
                text: 'El ticket se ha generado correctamente.',
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'http://localhost/SistemaCasino2/';
                }
            });
            </script>";
        } else {
            // Manejar error en el registro
            echo "<script>
            Swal.close(); // Cerrar el loading
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al registrar el ticket en la base de datos.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            </script>";
        }
    } else {
        // Manejar error en la generación del archivo
        echo "<script>
        Swal.close(); // Cerrar el loading
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al generar el ticket.',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'http://localhost/SistemaCasino2/';
        });
        </script>";
    }
}
