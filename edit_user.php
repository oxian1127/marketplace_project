<?php
// edit_user.php
// Iniciar sesión de manera segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación del usuario
if (!isset($_SESSION['user_id'])) {
    header("Location: app/public/index.php");
    exit();
}

// Cargar configuración de base de datos PDO
require_once 'app/config/database.php';

// Inicializar variables
$error = '';
$success = '';
$user = [];
$current_profile_pic = 'public/profile_pics/default-avatar.jpg';

try {
    // Obtener ID del usuario desde la sesión
    $user_id = $_SESSION['user_id'];

    // 1. Obtener datos actuales del usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Error: Usuario no encontrado");
    }

    $current_profile_pic = $user['profile_pic'] ?? 'default-avatar.jpg';

    // 2. Procesar formulario de actualización
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitizar y validar entradas
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
        $correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // Validaciones iniciales
        if (empty($username) || empty($correo)) {
            throw new Exception("Todos los campos requeridos deben estar completos");
        }

        // Validar formato de teléfono
        if (!empty($telefono) && !preg_match('/^\+?[0-9]{7,15}$/', $telefono)) {
            throw new Exception("Formato de teléfono inválido. Ejemplo válido: +57123456789");
        }

        // Verificar contraseña actual para cambios sensibles
        $sensitive_change = false;
        if ($correo !== $user['correoelectronico'] || $telefono !== $user['telefono'] || !empty($new_password)) {
            if (empty($current_password) || !password_verify($current_password, $user['password'])) {
                throw new Exception("Contraseña actual incorrecta para realizar cambios sensibles");
            }
            $sensitive_change = true;
        }

        // Validar nueva contraseña
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                throw new Exception("Las nuevas contraseñas no coinciden");
            }
            if (strlen($new_password) < 8) {
                throw new Exception("La contraseña debe tener al menos 8 caracteres");
            }
        }

        // 3. Manejar subida de imagen de perfil
        $profile_pic = $current_profile_pic;
        if ($_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo y tamaño de imagen
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['profile_pic']['tmp_name']);

            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Solo se permiten imágenes JPG, PNG o GIF");
            }

            if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                throw new Exception("El tamaño de la imagen no debe exceder 2MB");
            }

            // Generar nombre único y mover archivo
            $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $extension;
            $target_dir = "profile_pics/";

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_dir . $new_filename)) {
                throw new Exception("Error al guardar la nueva imagen de perfil");
            }

            // Eliminar imagen anterior si no es la predeterminada
            if ($current_profile_pic !== 'default-avatar.jpg') {
                @unlink($target_dir . $current_profile_pic);
            }

            $profile_pic = $new_filename;
        }

        // 4. Construir consulta de actualización
        $update_fields = [
            'username' => $username,
            'telefono' => $telefono,
            'correoelectronico' => $correo,
            'profile_pic' => $profile_pic
        ];

        $params = $update_fields;
        $set_clause = [];

        // Agregar contraseña si se cambió
        if (!empty($new_password)) {
            $update_fields['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            $set_clause[] = 'password = :password';
        }

        // Construir consulta SQL
        $sql = "UPDATE users SET 
                username = :username,
                telefono = :telefono,
                correoelectronico = :correoelectronico,
                profile_pic = :profile_pic"
            . (!empty($new_password) ? ', password = :password' : '')
            . " WHERE id = :user_id";

        $params['user_id'] = $user_id;

        // Ejecutar actualización
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            throw new Exception("Error al actualizar el perfil");
        }

        // Actualizar datos de sesión
        $_SESSION['username'] = $username;
        $_SESSION['profile_pic'] = $profile_pic;

        $success = "Perfil actualizado correctamente";

        // Opcional: Enviar correo de confirmación si cambió el email
        if ($correo !== $user['correoelectronico']) {
            // Implementar lógica de envío de correo aquí
        }
    }

} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 2rem auto;
        }

        .profile-pic-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid #dee2e6;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .profile-pic-preview:hover {
            transform: scale(1.05);
        }

        .password-note {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'app/Views/partials/navegacion.php'; ?>

    <div class="container">
        <div class="profile-container card shadow">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center">
                    <i class="bi bi-person-gear me-2"></i>Editar Perfil
                </h2>

                <!-- Mostrar mensajes de error/éxito -->
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Sección de imagen de perfil -->
                    <div class="mb-4 text-center">
                        <label for="profilePicInput" class="d-block">
                            <img src="public/profile_pics/<?= htmlspecialchars($current_profile_pic) ?>"
                                class="profile-pic-preview rounded-circle mb-2" id="profilePicPreview"
                                onerror="this.src='public/profile_pics/default-avatar.jpg'">
                        </label>
                        <input type="file" class="form-control visually-hidden" id="profilePicInput" name="profile_pic"
                            accept="image/*" onchange="previewProfilePic(event)">
                        <small class="text-muted">Haz clic en la imagen para cambiarla (max 2MB)</small>
                    </div>

                    <!-- Campos del formulario -->
                    <div class="mb-3">
                        <label class="form-label">Nombre de usuario</label>
                        <input type="text" class="form-control" name="username"
                            value="<?= htmlspecialchars($user['username']) ?>" required minlength="3" maxlength="50">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="telefono"
                            value="<?= htmlspecialchars($user['telefono']) ?>" pattern="\+?[0-9]{7,15}"
                            title="7-15 dígitos, formato internacional opcional">
                        <small class="text-muted">Ejemplo: +57123456789</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" name="correo"
                            value="<?= htmlspecialchars($user['correoelectronico']) ?>" required>
                    </div>

                    <!-- Sección de contraseñas -->
                    <div class="mb-4 border-top pt-3">
                        <h5 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Seguridad</h5>

                        <div class="mb-3">
                            <label class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" name="current_password" required>
                            <small class="password-note">Requerida para confirmar cambios</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" name="new_password" minlength="8"
                                placeholder="Dejar en blanco para no cambiar">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Previsualización de imagen de perfil
        function previewProfilePic(event) {
            const preview = document.getElementById('profilePicPreview');
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

        // Validación antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function (e) {
            const newPass = document.querySelector('input[name="new_password"]');
            const confirmPass = document.querySelector('input[name="confirm_password"]');

            if (newPass.value !== confirmPass.value) {
                e.preventDefault();
                alert('Las contraseñas nuevas no coinciden');
            }
        });
    </script>
</body>

</html>