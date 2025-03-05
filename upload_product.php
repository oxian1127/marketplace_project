<?php
// Incluir configuración de base de datos PDO
require_once 'app/config/database.php';
session_start();

// Redirigir si no hay sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: app/public/index.php");
    exit();
}

// Inicializar variables
$error = '';
$success = '';
$category_id = 0;
$categories = [];
$user_id = $_SESSION['user_id'];

try {
    // Obtener categorías válidas
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Sanitizar nombres de categoría
        $clean_name = trim(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'));
        if (!empty($clean_name)) {
            $categories[$row['id']] = $clean_name;
        }
    }

    // Procesar formulario POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitizar y validar entradas
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

        // Validaciones básicas
        if (empty($title) || strlen($title) > 255) {
            throw new Exception('El título debe tener entre 1 y 255 caracteres');
        }
        
        if (empty($description)) {
            throw new Exception('La descripción es obligatoria');
        }

        if (!is_numeric($price) || $price <= 0) {
            throw new Exception('El precio debe ser un número mayor a 0');
        }

        if (!array_key_exists($category_id, $categories)) {
            throw new Exception('Seleccione una categoría válida');
        }

        // Validar archivo subido
        if (empty($_FILES['image']['name'])) {
            throw new Exception('Debe seleccionar una imagen');
        }

        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Formato de imagen no válido (solo JPG, PNG o GIF)');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('La imagen es demasiado grande (máximo 2MB)');
        }

        // Generar nombre único para la imagen
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $extension;
        $target_dir = "images/";
        
        // Crear directorio si no existe
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Mover archivo subido
        if (!move_uploaded_file($file['tmp_name'], $target_dir . $image_name)) {
            throw new Exception('Error al subir la imagen');
        }

        // Insertar en base de datos
        $stmt = $pdo->prepare("INSERT INTO products 
            (user_id, title, description, price, image, category_id)
            VALUES (:user_id, :title, :description, :price, :image, :category_id)");

        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':image' => $image_name,
            ':category_id' => $category_id
        ]);

        $success = 'Producto subido exitosamente!';
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
    <title>Subir Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .upload-container { max-width: 800px; margin: 0 auto; }
        .preview-image { max-width: 300px; height: auto; display: none; margin: 1rem auto; border-radius: 8px; }
    </style>
</head>

<body class="bg-light">
    <?php include 'app/Views/partials/navegacion.php'; ?>

    <div class="container">
        <div class="upload-container">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="mb-4 text-center"><i class="bi bi-upload me-2"></i>Subir Producto</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <!-- Campos del formulario -->
                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="title" required 
                                   value="<?= isset($title) ? htmlspecialchars($title) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="3" required><?= 
                                isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Seleccione categoría</option>
                                <?php foreach ($categories as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= ($category_id == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="price" 
                                       step="0.01" min="0" required 
                                       value="<?= isset($price) ? htmlspecialchars($price) : '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Imagen</label>
                            <input type="file" class="form-control" name="image" accept="image/*" required
                                   onchange="previewImage(event)">
                            <img id="preview" class="preview-image" alt="Vista previa">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload me-2"></i>Publicar
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.style.display = 'block';
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        }
    </script>
</body>
</html>