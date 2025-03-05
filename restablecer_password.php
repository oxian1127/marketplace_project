<?php
// restablecer_password.php
session_start();
require_once __DIR__ . '/config/database.php'; // Conexión PDO corregida

/**
 * Verificación de sesión válida
 */
if (!isset($_SESSION['email_verificado'])) {
    header("Location: recuperar_password.php");
    exit();
}

$email = $_SESSION['email_verificado'];
$error = "";

/**
 * Procesamiento del formulario
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar que existe conexión a la base de datos
        if (!isset($pdo)) {
            throw new Exception("Error de conexión a la base de datos");
        }

        $nueva_password = $_POST['nueva_password'];
        $confirmar_password = $_POST['confirmar_password'];
        
        // Validar coincidencia de contraseñas
        if ($nueva_password !== $confirmar_password) {
            throw new Exception("Las contraseñas no coinciden");
        }
        
        // Hashear la nueva contraseña
        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizar base de datos usando PDO
        $stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE correoelectronico = :email");
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            // Destruir sesión y mostrar éxito
            session_destroy();
            $success = true;
        } else {
            throw new Exception("Error al actualizar la contraseña");
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    
    <!-- Estilos consistentes con el sistema -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .auth-card {
            max-width: 500px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .password-strength {
            height: 4px;
            transition: all 0.3s;
        }
        .height-100 {
            min-height: 100vh;
        }
    </style>
</head>

<body class="bg-light height-100">
    <?php include 'partials/navegacion.php'; ?>
    
    <div class="container d-flex align-items-center justify-content-center flex-grow-1">
        <div class="auth-card card shadow-lg w-100">
            <div class="card-body p-5">
                <?php if (isset($success)): ?>
                    <!-- Mensaje de éxito -->
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-success display-4"></i>
                        <h2 class="mt-3 fw-bold">¡Contraseña actualizada!</h2>
                        <p class="mt-3">Ahora puedes iniciar sesión con tu nueva contraseña</p>
                        <a href="login.php" class="btn btn-success mt-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ir al login
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Formulario de restablecimiento -->
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock text-primary display-4"></i>
                        <h1 class="h3 mt-2 fw-bold">Nueva Contraseña</h1>
                        <p class="text-muted">Para: <?= htmlspecialchars($email) ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nueva contraseña</label>
                            <input type="password" name="nueva_password" 
                                   class="form-control form-control-lg"
                                   required minlength="8"
                                   placeholder="Mínimo 8 caracteres">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirmar contraseña</label>
                            <input type="password" name="confirmar_password" 
                                   class="form-control form-control-lg"
                                   required minlength="8"
                                   placeholder="Repite tu contraseña">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                            <i class="bi bi-key-fill me-2"></i>Cambiar Contraseña
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>