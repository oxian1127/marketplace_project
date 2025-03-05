<?php
session_start();
require_once __DIR__ . '\app\config\database.php'; // Asegúrate que la ruta es correcta

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar si la conexión PDO está disponible
        if (!isset($pdo)) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        $correoelectronico = $_POST['correoelectronico'];
        
        // Consulta preparada con PDO
        $stmt = $pdo->prepare("SELECT id FROM users WHERE correoelectronico = :correoelectronico");
        $stmt->bindParam(':correoelectronico', $correoelectronico, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $_SESSION['email_verificado'] = $correoelectronico;
            header("Location: restablecer_password.php");
            exit();
        } else {
            $error = "El email no está registrado.";
        }
    } catch (PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
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
    <title>Recuperar Contraseña</title>
    
    <!-- Estilos consistentes con tu sistema -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .auth-card {
            max-width: 500px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .auth-icon {
            font-size: 3rem;
            color: #0d6efd;
        }
        .height-100 {
            min-height: 100vh;
        }
    </style>
</head>

<body class="bg-light height-100">
    <?php include 'app/Views/partials/navegacion.php'; ?>
    
    <div class="container d-flex align-items-center justify-content-center flex-grow-1">
        <div class="auth-card card shadow-lg w-100">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock auth-icon"></i>
                    <h1 class="h3 mt-2 fw-bold">Recuperar Contraseña</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Correo electrónico</label>
                        <input type="email" name="correoelectronico" class="form-control form-control-lg"
                               required autofocus placeholder="ejemplo@dominio.com">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                        <i class="bi bi-envelope-check me-2"></i>Continuar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>