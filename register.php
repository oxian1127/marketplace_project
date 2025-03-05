<?php
include 'app/config/database.php';
session_start();

$error = '';
$success = '';
$default_avatar = 'public/profile_pics/default-avatar.jpg';
$upload_dir = 'public/profile_pics';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $telefono = trim($_POST['telefono']);
    $correoelectronico = trim($_POST['correoelectronico']);
    $profile_pic = $default_avatar;

    // Procesar imagen de perfil
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $file_type = $_FILES['profile_pic']['type'];
        $file_size = $_FILES['profile_pic']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $error = 'Formato de imagen no válido (solo JPG, PNG o GIF)';
        } elseif ($file_size > $max_size) {
            $error = 'La imagen es demasiado grande (máximo 2MB)';
        } else {
            $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid('avatar_') . '.' . $extension;
            $target_path = $upload_dir . $profile_pic;

            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
                $profile_pic = $default_avatar;
                $error = 'Error al subir la imagen de perfil';
            }
        }
    }

    if (empty($error)) {
        // Verificar si el usuario ya existe
        $check_sql = "SELECT * FROM users WHERE username = ? OR correoelectronico = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $correoelectronico);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'El usuario o correo electrónico ya están registrados';
        } else {
            $sql = "INSERT INTO users (username, password, telefono, correoelectronico, profile_pic) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $password, $telefono, $correoelectronico, $profile_pic);

            if ($stmt->execute()) {
                $success = '¡Registro exitoso! <a href="app/Controllers/AuthController.php" class="alert-link">Iniciar sesión</a>';
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(120deg, #7f7fd5, #86a8e7, #91eae4);
            min-height: 100vh;
        }

        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .upload-label {
            display: block;
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .upload-label input[type="file"] {
            display: none;
        }

        .upload-text {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="public/index.php">Marketplace</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="app/Controllers/AuthController.php">Iniciar sesión</a>
            </div>
        </div>
    </nav>

    <br>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <label class="upload-label">
                                <img src="public/profile_pics/default-avatar.jpg/<?php echo $default_avatar; ?>"
                                    id="avatarPreview" class="avatar-preview mb-3" alt="Vista previa del avatar">
                                <input type="file" id="uploadTrigger" name="profile_pic" accept="image/*">
                                <span class="upload-text">Haz clic para cambiar la foto</span>
                            </label>
                            <h3 class="mb-3">Crear cuenta</h3>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="register.php" enctype="multipart/form-data">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Usuario" required>
                                <label for="username"><i class="bi bi-person-circle me-2"></i>Nombre de usuario</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Contraseña" required>
                                <label for="password"><i class="bi bi-lock me-2"></i>Contraseña</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                    placeholder="Teléfono" required>
                                <label for="telefono"><i class="bi bi-phone me-2"></i>Teléfono</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="email" class="form-control" id="correoelectronico" name="correoelectronico"
                                    placeholder="Correo Electrónico" required>
                                <label for="correoelectronico"><i class="bi bi-envelope me-2"></i>Correo
                                    Electrónico</label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Registrarse
                                </button>
                                <a href="public/index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <span class="text-muted">¿Ya tienes cuenta? </span>
                            <a href="app/Controllers/AuthController.php" class="text-decoration-none">Inicia sesión
                                aquí</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejo de la imagen de perfil
        const avatarPreview = document.getElementById('avatarPreview');
        const uploadTrigger = document.getElementById('uploadTrigger');

        // Mostrar vista previa de la imagen seleccionada
        uploadTrigger.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    avatarPreview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Permitir arrastrar y soltar imagen
        avatarPreview.addEventListener('dragover', (e) => {
            e.preventDefault();
            avatarPreview.style.transform = 'scale(1.1)';
        });

        avatarPreview.addEventListener('dragleave', () => {
            avatarPreview.style.transform = 'scale(1)';
        });

        avatarPreview.addEventListener('drop', (e) => {
            e.preventDefault();
            avatarPreview.style.transform = 'scale(1)';
            const file = e.dataTransfer.files[0];
            if (file) uploadTrigger.files = e.dataTransfer.files;
        });
    </script>
</body>

</html>