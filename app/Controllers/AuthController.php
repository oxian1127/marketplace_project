<?php
// Iniciar sesión para manejar la autenticación
session_start();

// Incluir archivo de configuración de la base de datos
require_once '../config/database.php'; // Cambiar a la conexión PDO

// Variable para almacenar errores
$error = '';

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y obtener datos del formulario
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Preparar consulta SQL con parámetros para prevenir inyecciones
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        // Verificar si se encontró el usuario
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar contraseña con hash seguro
            if (password_verify($password, $row['password'])) {
                // Configurar variables de sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['profile_pic'] = $row['profile_pic'];

                // Redireccionar al dashboard
                header("Location: ../../public/index.php");
                exit();
            } else {
                $error = 'Contraseña incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
    } catch (PDOException $e) {
        // Manejar errores de base de datos
        $error = 'Error en el sistema: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Configuración de caracteres y vista -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Marketplace</title>

    <!-- Enlaces a Bootstrap y estilos personalizados -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* Estilos personalizados */
        body {
            background: linear-gradient(120deg, #7f7fd5, #86a8e7, #91eae4);
            min-height: 100vh;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-floating:focus-within {
            z-index: 2;
        }
    </style>
</head>

<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../../public/index.php">
                <i class="bi bi-shop me-2"></i>Marketplace
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../../register.php">
                    <i class="bi bi-person-plus me-2"></i>Registrarse
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <!-- Tarjeta de login -->
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h3 class="mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión</h3>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Formulario de login -->
                        <form method="post" action="../Controllers/AuthController.php">
                            <!-- Campo de usuario -->
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Usuario" required autofocus>
                                <label for="username">
                                    <i class="bi bi-person-circle me-2"></i>Usuario
                                </label>
                            </div>

                            <!-- Campo de contraseña -->
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Contraseña" required>
                                <label for="password">
                                    <i class="bi bi-lock me-2"></i>Contraseña
                                </label>
                            </div>

                            <!-- Botón de submit -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                                </button>
                            </div>

                            <!-- Opciones adicionales -->
                            <div class="text-center">
                                <div class="mb-2">
                                    <a href="../../recuperar_password.php" class="text-decoration-none">
                                        <i class="bi bi-question-circle me-2"></i>¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Enlace a registro -->
                        <div class="text-center mt-4">
                            <span class="text-muted">¿No tienes cuenta? </span>
                            <p><a href="../../register.php" class="text-decoration-none">
                                    <i class="bi bi-person-plus me-2"></i>Regístrate aquí
                                </a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>