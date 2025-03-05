<?php
// negocios.php
session_start();
require_once '../config/database.php';

// Función para contar productos de un negocio
function contarProductos($pdo, $negocio_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = :negocio_id");
    $stmt->execute([':negocio_id' => $negocio_id]);
    return $stmt->fetchColumn();
}

try {
    // Consulta modificada: eliminada la condición del role
    $stmt = $pdo->query("SELECT * FROM users 
                        WHERE business_name != '' 
                        ORDER BY created_at DESC");
    $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar negocios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Negocios</title>

    <!-- Bootstrap + Iconos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        .tarjeta-negocio .position-relative {
            min-height: 250px; /* Mantenemos altura mínima del contenedor */
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: #f8f9fa; /* Fondo para imágenes con espacio sobrante */
        }

        .tarjeta-negocio:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }

        .tarjeta-imagen {
            height: auto; /* Cambiamos de altura fija a automática */
        max-height: 250px; /* Altura máxima igual que antes */
        width: 100%; /* Ocupa todo el ancho disponible */
        object-fit: scale-down; /* Cambiamos 'cover' por 'scale-down' */
        object-position: center;
        display: block; /* Elimina espacio fantasma debajo de la imagen */
        }

        .info-categoria {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 5px 15px;
        }

        .badge-productos {
            top: 15px;
            right: 15px;
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>

<body>
    <?php include '../Views/partials/navegacion.php'; ?>

    <main class="container py-5">
        <h5 class="text-center mb-5 display-6 fw-bold text-primary">
            Todos los Negocios Registrados
            <hr>
        </h5>

        <?php if (empty($negocios)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>No hay negocios registrados actualmente
            </div>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($negocios as $negocio): ?>
                <div class="col">
                    <div class="tarjeta-negocio card h-100 border-0 shadow-lg">
                        <div class="position-relative">
                            <!-- Imagen corregida con ruta profile_pics -->
                            <img src="../../public/profile_pics/<?= htmlspecialchars($negocio['profile_pic'] ?? 'default-store.jpg') ?>"
                                class="tarjeta-imagen card-img-top" width="auto" height="auto" alt="<?= htmlspecialchars($negocio['business_name']) ?>"
                                onerror="this.src='profile_pics/default-store.jpg'">

                            <span class="badge-productos badge bg-primary rounded-pill position-absolute">
                                <i class="bi bi-box-seam me-1"></i><?= contarProductos($pdo, $negocio['id']) ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <h3 class="card-title mb-3 fw-bold">
                                <?= htmlspecialchars($negocio['business_name']) ?>
                            </h3>

                            <div class="d-flex align-items-center mb-3">
                                <span class="info-categoria">
                                    <i class="bi bi-tag-fill me-2 text-primary"></i>
                                    <?= !empty($negocio['business_category']) ?
                                        htmlspecialchars($negocio['business_category']) :
                                        'Sin categoría' ?>
                                </span>
                            </div>

                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-globe2 me-2 text-primary"></i>
                                    <?php if (!empty($negocio['website'])): ?>
                                        <a href="<?= htmlspecialchars($negocio['website']) ?>"
                                            class="text-decoration-none link-primary" target="_blank" rel="noopener noreferrer">
                                            Visitar sitio web
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Sin sitio web</span>
                                    <?php endif; ?>
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-calendar3 me-2 text-primary"></i>
                                    Registrado: <?= date('d/m/Y', strtotime($negocio['created_at'])) ?>
                                </li>
                            </ul>
                        </div>

                        <div class="card-footer bg-transparent border-0 pb-3 pt-0">
                            <a href="profile.php?id=<?= $negocio['id'] ?>" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-arrow-right-circle me-2"></i>Ver Negocio
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>