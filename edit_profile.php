<?php
require_once 'app/config/database.php';
session_start();

// Verificar si GD está instalado
$gdInstalled = extension_loaded('gd');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Obtener datos de sesión
$isLoggedIn = true;
$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['profile_pic'] ?? 'default-avatar.jpg';

try {
    // Obtener número de productos
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
    $stmtCount->execute([$user_id]);
    $numProducts = $stmtCount->fetchColumn();

    // Obtener datos actuales del usuario
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $business_name = htmlspecialchars(trim($_POST['business_name']));
        $business_description = htmlspecialchars(trim($_POST['business_description']));
        $business_category = htmlspecialchars(trim($_POST['business_category']));
        $website = filter_var(trim($_POST['website']), FILTER_SANITIZE_URL);

        // Validaciones
        $errors = [];

        if (empty($business_name)) {
            $errors[] = "El nombre del negocio es obligatorio";
        }

        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = "El formato del sitio web no es válido";
        }

        // Manejar subida de header
        $profile_header = $user['profile_header'];
        if ($_FILES['header_image']['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_type = mime_content_type($_FILES['header_image']['tmp_name']);

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Solo se permiten imágenes JPG o PNG";
            } else {
                $extension = strtolower(pathinfo($_FILES['header_image']['name'], PATHINFO_EXTENSION));
                $new_filename = uniqid() . '.' . $extension;
                $target = "headers/" . $new_filename;

                // Crear directorio si no existe
                if (!file_exists('headers')) {
                    mkdir('headers', 0755, true);
                }

                if ($gdInstalled) {
                    // Procesamiento con GD
                    list($width, $height) = getimagesize($_FILES['header_image']['tmp_name']);
                    $new_height = 300;
                    $new_width = ($width / $height) * $new_height;

                    $image = imagecreatetruecolor($new_width, $new_height);
                    $source = ($file_type == 'image/jpeg') ?
                        imagecreatefromjpeg($_FILES['header_image']['tmp_name']) :
                        imagecreatefrompng($_FILES['header_image']['tmp_name']);

                    imagecopyresampled($image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    $success = false;
                    if ($file_type == 'image/jpeg') {
                        $success = imagejpeg($image, $target, 80);
                    } elseif ($file_type == 'image/png') {
                        $success = imagepng($image, $target, 8);
                    }

                    if (!$success) {
                        $errors[] = "Error al procesar la imagen con GD";
                    }
                } else {
                    // Subida simple sin procesamiento
                    if (!move_uploaded_file($_FILES['header_image']['tmp_name'], $target)) {
                        $errors[] = "Error al subir la imagen";
                    }
                }

                if (empty($errors)) {
                    $profile_header = $new_filename;
                    if ($user['profile_header'] != 'default-header.jpg') {
                        @unlink("headers/" . $user['profile_header']);
                    }
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET 
                              business_name = ?,
                              business_description = ?,
                              business_category = ?,
                              website = ?,
                              profile_header = ?
                              WHERE id = ?");
            $stmt->execute([
                $business_name,
                $business_description,
                $business_category,
                $website,
                $profile_header,
                $user_id
            ]);

            if ($stmt->rowCount() > 0) {
                $success = "Perfil actualizado correctamente";
                $_SESSION['profile_header'] = $profile_header;
                // Actualizar datos locales
                $user = array_merge($user, [
                    'business_name' => $business_name,
                    'business_description' => $business_description,
                    'business_category' => $business_category,
                    'website' => $website,
                    'profile_header' => $profile_header
                ]);
            } else {
                $errors[] = "Error al actualizar el perfil";
            }
        }

        if (!empty($errors)) {
            $error = implode("<br>", $errors);
        }
    }

    // Obtener categorías
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Perfil Comercial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .header-preview {
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 2px dashed #dee2e6;
        }

        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
            }

            .header-preview {
                height: 120px;
            }
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <?php include 'app/Views/partials/navegacion.php'; ?>

    <div class="container">
        <div class="profile-container">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="mb-4"><i class="bi bi-shop me-2"></i>Editar Perfil Comercial</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> ¡Datos guardados exitosamente!
                            <div class="mt-3">
                                <a href="app/Controllers/profile.php?id=<?= $user_id ?>" class="btn btn-success">
                                    <i class="bi bi-shop me-2"></i> Ver mi perfil
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label">Imagen de cabecera</label>
                            <div class="header-preview"
                                style="background-image: url('headers/<?= $user['profile_header'] ?>')"></div>
                            <input type="file" class="form-control" name="header_image" accept="image/*">
                            <small class="text-muted">Tamaño recomendado: 1200x300 px (JPEG o PNG)</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del negocio *</label>
                                <input type="text" class="form-control" name="business_name"
                                    value="<?= htmlspecialchars($user['business_name']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Categoría principal</label>
                                <!-- Modificación en el select de categorías -->
                                <select class="form-select" name="business_category" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>"
                                            <?= ($user['business_category'] == $category['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción del negocio</label>
                                <textarea class="form-control" name="business_description" rows="4"
                                    placeholder="Describa su negocio, productos y servicios..."><?=
                                        htmlspecialchars($user['business_description']) ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Sitio web</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                    <input type="url" class="form-control" name="website"
                                        value="<?= htmlspecialchars($user['website']) ?>"
                                        placeholder="https://ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i> Guardar Cambios
                            </button>
                            <a href="app/Controllers/profile.php?id=<?= $user_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview de imagen de cabecera
        document.querySelector('input[name="header_image"]').addEventListener('change', function (e) {
            const reader = new FileReader();
            reader.onload = function () {
                document.querySelector('.header-preview').style.backgroundImage = `url(${reader.result})`;
            }
            if (this.files[0]) reader.readAsDataURL(this.files[0]);
        });
    </script>
</body>

</html>