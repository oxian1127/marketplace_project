<?php
// Iniciar sesión de manera segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: app/public/index.php");
    exit();
}

// Cargar configuración de base de datos PDO
require_once 'app/config/database.php';

// Inicializar variables
$product = [];
$categories = [];
$error = '';

try {
    // Obtener ID de producto y usuario
    $user_id = $_SESSION['user_id'];
    $product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$product_id) {
        header("Location: dashboard.php");
        exit();
    }

    // 1. Obtener categorías
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name]

    // 2. Obtener datos del producto
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $product_id, ':user_id' => $user_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: dashboard.php");
        exit();
    }

    // 3. Procesar formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar y sanitizar entradas
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $deleted_images = json_decode($_POST['deleted_images'], true) ?? [];

        // Validaciones básicas
        if (empty($title) || strlen($title) > 255) {
            throw new Exception("El título debe tener entre 1 y 255 caracteres");
        }

        if ($price <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }

        if (!array_key_exists($category_id, $categories)) {
            throw new Exception("Categoría inválida");
        }

        // Manejo de imágenes
        $current_images = explode(",", $product['image']);
        $updated_images = array_diff($current_images, $deleted_images);

        // Eliminar archivos marcados
        foreach ($deleted_images as $image) {
            $file_path = "images/" . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Subir nuevas imágenes
        $new_images = [];
        if (!empty($_FILES['new_images']['name'][0])) {
            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmpName) {
                $file_type = mime_content_type($tmpName);
                if (!in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                    continue; // Saltar archivos no válidos
                }

                $extension = pathinfo($_FILES['new_images']['name'][$key], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $extension;
                $target = "images/" . $new_filename;

                if (move_uploaded_file($tmpName, $target)) {
                    $new_images[] = $new_filename;
                }
            }
        }

        // Combinar imágenes
        $all_images = array_merge($updated_images, $new_images);

        if (empty($all_images)) {
            throw new Exception("Debe haber al menos una imagen");
        }

        // Actualizar base de datos
        $stmt = $pdo->prepare("UPDATE products SET 
                            title = :title,
                            description = :description,
                            price = :price,
                            category_id = :category_id,
                            image = :image,
                            updated_at = NOW()
                            WHERE id = :id AND user_id = :user_id");

        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':category_id' => $category_id,
            ':image' => implode(",", $all_images),
            ':id' => $product_id,
            ':user_id' => $user_id
        ]);

        header("Location: dashboard.php");
        exit();
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
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .image-preview {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            margin: 5px;
        }

        .image-card {
            position: relative;
            margin: 10px;
            transition: all 0.3s;
        }

        .image-card.deleting {
            opacity: 0.5;
            transform: scale(0.95);
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #dc3545;
            background: rgba(255, 255, 255, 0.8);
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timestamp {
            font-size: 0.9rem;
            color: #666;
        }

        #restoreButton {
            display: none;
            margin-left: 10px;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'app/Views/partials/navegacion.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="mb-4 text-center"><i class="bi bi-pencil-square me-2"></i>Editar Producto</h2>

                        <div class="timestamp alert alert-info">
                            <div>
                                <i class="bi bi-calendar-plus"></i>
                                <strong>Publicación:</strong>
                                <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>
                            </div>
                            <?php if ($product['updated_at']): ?>
                                <div class="mt-2">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    <strong>Última actualización:</strong>
                                    <?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <hr>

                        <form method="post" id="editForm" enctype="multipart/form-data">
                            <input type="hidden" name="deleted_images" id="deletedImages" value="[]">

                            <div class="mb-3">
                                <label for="title" class="form-label">Título del producto</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?= htmlspecialchars($product['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    required><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoría</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $id => $name): ?>
                                        <option value="<?= $id ?>" <?= $id == $product['category_id'] ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="price" class="form-label">Precio</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01"
                                        min="0" value="<?= htmlspecialchars($product['price']) ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-images me-2"></i>Imágenes Actuales</h5>
                                <div class="d-flex flex-wrap" id="currentImages">
                                    <?php
                                    $currentImages = explode(",", $product['image']);
                                    foreach ($currentImages as $image) {
                                        echo '<div class="image-card" data-image="' . htmlspecialchars($image) . '">';
                                        echo '<img src="images/' . htmlspecialchars($image) . '" class="image-preview">';
                                        echo '<div class="delete-btn" onclick="toggleDeleteImage(this)">';
                                        echo '<i class="bi bi-trash"></i>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <button type="button" id="restoreButton" class="btn btn-sm btn-warning mt-2"
                                    onclick="restoreImages()">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Deshacer eliminaciones
                                </button>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-cloud-arrow-up me-2"></i>Agregar Nuevas Imágenes</h5>
                                <input type="file" class="form-control" id="new_images" name="new_images[]" multiple
                                    accept="image/*" onchange="previewNewImages(event)">
                                <div id="newImagesPreview" class="d-flex flex-wrap mt-3"></div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-danger" id="cancelButton">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error -->
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let deletedImages = [];
        let originalImages = <?= json_encode(explode(",", $product['image'])) ?>;

        function toggleDeleteImage(btn) {
            const imageCard = btn.closest('.image-card');
            const imageName = imageCard.dataset.image;

            if (deletedImages.includes(imageName)) {
                // Restaurar imagen
                imageCard.classList.remove('deleting');
                deletedImages = deletedImages.filter(img => img !== imageName);
            } else {
                // Marcar para eliminar
                imageCard.classList.add('deleting');
                deletedImages.push(imageName);
            }

            updateDeletedImagesInput();
            updateRestoreButton();
        }

        function updateRestoreButton() {
            const restoreBtn = document.getElementById('restoreButton');
            restoreBtn.style.display = deletedImages.length > 0 ? 'inline-block' : 'none';
        }

        function restoreImages() {
            document.querySelectorAll('.image-card.deleting').forEach(card => {
                card.classList.remove('deleting');
            });
            deletedImages = [];
            updateDeletedImagesInput();
            updateRestoreButton();
        }

        function updateDeletedImagesInput() {
            document.getElementById('deletedImages').value = JSON.stringify(deletedImages);
        }

        function previewNewImages(event) {
            const previewContainer = document.getElementById('newImagesPreview');
            previewContainer.innerHTML = '';

            Array.from(event.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    img.style.maxWidth = '200px';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }

        // Validación al enviar el formulario
        document.getElementById('editForm').addEventListener('submit', function (e) {
            const remainingImages = originalImages.length - deletedImages.length;
            const newImages = document.getElementById('new_images').files.length;

            if ((remainingImages + newImages) === 0) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('errorMessage').textContent =
                    'Debes tener al menos una imagen en el producto';
                modal.show();
            }
        });

        // Cancelar cambios
        document.getElementById('cancelButton').addEventListener('click', function (e) {
            if (deletedImages.length > 0 || document.getElementById('new_images').files.length > 0) {
                if (!confirm('¿Estás seguro de cancelar? Se perderán todos los cambios no guardados')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>

</html>