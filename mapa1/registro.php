<?php
session_start();
?>
<?php
if (!empty($_SESSION['flash_error'])) {
    echo '<div class="alert alert-danger" role="alert">'
        . htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8')
        . '</div>';
    unset($_SESSION['flash_error']);
}

if (!empty($_SESSION['flash_success'])) {
    echo '<div class="alert alert-success" role="alert">'
        . htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8')
        . '</div>';
    unset($_SESSION['flash_success']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Rastreo de tu Técnico</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6610f2, #007bff);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .register-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="register-title">Crear Cuenta</h2>
        <form method="POST" action="procesar_registro.php">
            <div class="mb-3">
                <label for="contrato" class="form-label">Contrato</label>
                <input type="text" class="form-control" id="contrato" name="contrato" placeholder="Ingresa tu número de contrato" required>
            </div>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Crea una contraseña" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-success">Registrarse</button>
            </div>
        </form>
        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
</body>
</html>
