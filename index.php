<!DOCTYPE html>
<html lang="es">

<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Casino Prize</title>
    <head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .btn {
        transition: background-color 0.3s, transform 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .input-group {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    #rut {
        border-radius: 8px;
        padding: 15px; /* Aumentado el padding */
        font-size: 1.8rem; /* Aumentado el tamaño de fuente */
        height: 80px; /* Aumentado la altura */
        text-align: center;
        vertical-align: middle;
    }

    .keypad-btn {
        width: 100%;
        height: 120px; /* Aumentado la altura de los botones */
        font-size: 32px; /* Aumentado el tamaño de la fuente */
        margin-bottom: 15px; /* Espaciado entre botones */
        color: #fff;
        border: none;
    }

    .keypad-btn-primary {
        background-color: #007bff;
    }

    .keypad-btn-primary:hover {
        background-color: #0056b3;
    }

    .keypad-btn-secondary {
        background-color: #ff6f61;
    }

    .keypad-btn-secondary:hover {
        background-color: #e6514c;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-block {
        width: 100%;
        display: block;
    }

    @media (max-width: 576px) {
        .keypad-btn {
            height: 80px;
            font-size: 28px;
        }
    }
</style>

<script>
        // Captura el evento de la tecla "Enter" en el formulario
        document.addEventListener('DOMContentLoaded', function () {
            var formulario = document.getElementById('rutForm');
            formulario.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault(); // Evitar el comportamiento predeterminado
                    formulario.submit(); // Enviar el formulario
                }
            });
        });
        
    </script>
<script>
    function validarRUT() {
        const rutInput = document.getElementById('rut');
        const rut = rutInput.value.trim();  // Eliminar espacios en blanco
        const rutRegex = /^[0-9]{7,8}-[0-9kK]$/;  // Expresión regular para el formato correcto del RUT

        // Verificar si el campo está vacío
        if (rut === '') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El campo RUT no puede estar vacío.',
            });
            return false;
        }

        // Verificar si el RUT tiene el formato correcto
        if (!rutRegex.test(rut)) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El RUT ingresado no tiene un formato válido. Ejemplo: 1233344-5 o 6333444-k.',
            });
            return false;
        }

        // Si el RUT pasa las validaciones iniciales, proceder
        return true;  // Permitir el envío del formulario
    }
</script>



</head>

<body style="background-color: #D8D8D8;">
    <!-- Alerta personalizada -->
<div id="customAlert" class="alert" style="display:none;">
    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
    <p id="alertMessage"></p>
</div>

    <div class="container mt-5">
        <div class="row align-items-center mb-3 text-center">
            <div class="col-12 d-flex justify-content-center">
                <img src="images/logo.png" alt="Logo" class="img-fluid" style="max-width: 150px;">
            </div>

            <div class="col-12">
                <h2 class="mt-4 mb-0" style="font-size: 2.5rem;">CASINO PRIZE</h2>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-12">
                <h4 class="text-center mb-5" style="font-size: 1.5rem; font-weight: 500;">
                    <br>Ingrese su RUT
                </h4>


                <form id="rutForm" method="POST" action="procesar_rut.php" onsubmit="return validarRUT()">
                <div class="input-group mb-4">
                        <input type="text" id="rut" name="rut" class="form-control" readonly>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height: 100px; font-size: 28px;" onclick="addNumber('1')">1</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('2')">2</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('3')">3</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('4')">4</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('5')">5</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('6')">6</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('7')">7</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('8')">8</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('9')">9</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #FA6E00; color: white; width: 100%; height: 100px; font-size: 28px;" onclick="clearRut()">Borrar</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('0')">0</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn" style="background-color: #11779A; color: white; width: 100%; height:  100px; font-size: 28px;" onclick="addNumber('K')">K</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">

                            <button type="submit" class="btn btn-block" style="background-color: #548D03; color: white; width: 100%; height: 70px; font-size: 24px;">Aceptar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script>
        function addNumber(number) {
            let rutField = document.getElementById("rut");
            if (rutField.value.replace(/[.-]/g, '').length < 9) {
                rutField.value += number;
                formato_rut(rutField);
            }
        }

        function clearRut() {
            let rutField = document.getElementById("rut");
            rutField.value = rutField.value.slice(0, -1);
            formato_rut(rutField);
        }

        function formato_rut(rut) {
            rut.value = rut.value.replace(/[.-]/g, '').replace(/^(\d{1,2})(\d{3})(\d{3})(\w{1})$/, '$1.$2.$3-$4');
        }
    </script>

   // <script>


  
function showAlert(message, type = 'alert') {
    var alertBox = document.getElementById('customAlert');
    var alertMessage = document.getElementById('alertMessage');

    alertMessage.innerHTML = message;

    // Remover clases previas
    alertBox.classList.remove('success', 'warning', 'info', 'alert');
    
    // Asignar clase según el tipo de alerta
    alertBox.classList.add(type);

    alertBox.style.display = 'block';

    // Ocultar después de 3 segundos y redirigir si es necesario
    setTimeout(function() {
        alertBox.style.display = 'none';
    }, 3000);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

</html>