<?php
// Iniciar sesión de manera segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: public/index.php");
    exit();
}

// Cargar configuración de base de datos PDO
require_once 'app/config/database.php';

try {
    // Obtener datos del usuario
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';

    // Función para obtener productos del usuario con PDO
    function getUserProducts($user_id, $pdo)
    {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Manejar eliminación de productos
    if (isset($_GET['delete'])) {
        $product_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

        if ($product_id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                ':id' => $product_id,
                ':user_id' => $user_id
            ]);

            if ($stmt->rowCount() > 0) {
                header("Location: dashboard.php");
                exit();
            }
        }
    }

    // Obtener productos del usuario
    $userProducts = getUserProducts($user_id, $pdo);

    // Función para obtener detalles del usuario
    function getUserDetails($user_id, $pdo)
    {
        $stmt = $pdo->prepare("SELECT username, telefono, correoelectronico FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .product-card {
            transition: transform 0.2s;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'app/Views/partials/navegacion.php'; ?>

    <!-- Contenido principal -->
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="display-5 fw-bold text-primary">Mis Productos</h2>
                <?php if (empty($userProducts)): ?>
                    <div class="alert alert-info">No has subido ningún producto todavía</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($userProducts as $product): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card product-card shadow h-100">
                        <img src="images/<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image"
                            alt="<?= htmlspecialchars($product['title']) ?>">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($product['title']) ?></h5>
                            <span class="badge bg-success mb-2">
                                <?= htmlspecialchars(ucfirst($product['category'])) ?>
                            </span>
                            <p class="card-text text-muted"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary fs-6">
                                    $<?= number_format($product['price'], 0) ?>
                                </span>
                                <div class="btn-group">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal" data-product-id="<?= $product['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">
                                Publicado: <?= date('d/m/Y', strtotime($product['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que deseas eliminar este producto permanentemente?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a id="confirmDelete" href="#" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de eliminación
        const deleteModal = document.getElementById('deleteModal')
        deleteModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget
            const productId = button.getAttribute('data-product-id')
            const confirmButton = document.getElementById('confirmDelete')
            confirmButton.href = `dashboard.php?delete=${productId}`
        })
    </script>
</body>

</html>