<?php
session_start();
require_once '../config/database.php'; // Asegurar conexión PDO con UTF-8

// Configurar encoding para la conexión
$pdo->exec("SET NAMES 'utf8mb4'");

// Verificar sesión para navbar
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? 0;
$profilePic = $_SESSION['profile_pic'] ?? 'default-avatar.jpg';
$username = $_SESSION['username'] ?? '';

// Obtener ID del perfil a mostrar
$profileId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta para información del perfil
$stmt = $pdo->prepare("SELECT 
    u.*, 
    COUNT(p.id) AS total_products,
    DATE_FORMAT(u.created_at, '%b %Y') AS member_since
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    WHERE u.id = ?
    GROUP BY u.id");
$stmt->execute([$profileId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header("Location: ../public/index.php");
    exit();
}

// Consulta para productos del perfil con categorías
$stmtProducts = $pdo->prepare("SELECT 
    p.*, 
    c.name AS category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC");
$stmtProducts->execute([$profileId]);
$products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($profile['business_name'], ENT_QUOTES, 'UTF-8') ?> - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .profile-header {
            height: 300px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('../../headers/<?= $profile['profile_header'] ?>');
            background-size: cover;
            background-position: center;
        }

        .profile-avatar {
            margin-top: -75px;
            border: 5px solid white;
            width: 12rem;
            height: 12rem;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .business-info-title {
            color: #2c3e50;
            background-color: #f8f9fa;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            border-left: 4px solid #3498db;
        }

        .product-card {
            transition: transform 0.2s;
            height: 100%;
            min-height: 400px;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
        }

        .badge-category {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .profile-avatar {
                width: 8rem;
                height: 8rem;
                margin-top: -50px;
            }

            .business-info-title {
                font-size: 1.1rem;
                margin-bottom: 1rem;
            }

            .profile-avatar {
                margin-top: -50px;
                width: 100px;
                height: 100px;
            }

            .product-card {
                min-height: auto;
            }
        }
    </style>
</head>

<body class="bg-light">
    <?php include '../Views/partials/navegacion.php'; ?>
    <!-- Cabecera del perfil -->
    <div class="profile-header"></div>

    <main class="container mt-4">
        <div class="row">
            <!-- Sección izquierda - Información del negocio -->
            <div class="col-lg-4 text-center mb-4">
                <img src="../../public/profile_pics/<?= htmlspecialchars($profile['profile_pic'], ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded-circle profile-avatar"
                    alt="Avatar de <?= htmlspecialchars($profile['business_name'], ENT_QUOTES, 'UTF-8') ?>"
                    onerror="this.src='profile_pics/default-avatar.jpg'">

                <h1 class="mt-3 mb-3"><?= htmlspecialchars($profile['business_name'], ENT_QUOTES, 'UTF-8') ?></h1>

                <?php if ($userId == $profileId): ?>
                    <a href="../../edit_profile.php" class="btn btn-primary mb-4">
                        <i class="bi bi-pencil"></i> Editar Perfil
                    </a>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body text-start">
                        <h5 class="business-info-title">
                            <i class="bi bi-info-circle me-2"></i>Información del Negocio
                        </h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="bi bi-tags me-2 text-primary"></i>
                                <strong>Categoría Principal:</strong><br>
                                <?= !empty($profile['business_category'])
                                    ? htmlspecialchars($profile['business_category'], ENT_QUOTES, 'UTF-8')
                                    : '<span class="text-muted">No especificada</span>' ?>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-globe me-2 text-primary"></i>
                                <strong>Sitio web:</strong>
                                <?php if (!empty($profile['website'])): ?>
                                    <a href="<?= htmlspecialchars($profile['website']) ?>" target="_blank"
                                        class="text-decoration-none text-break">
                                        <?= parse_url($profile['website'], PHP_URL_HOST) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No proporciona sitio web</span>
                                <?php endif; ?>
                            </li>
                            <li class="mb-3">
                                <i class="bi bi-box me-2 text-primary"></i>
                                <strong>Productos publicados:</strong>
                                <?= $profile['total_products'] ?>
                            </li>
                            <li>
                                <i class="bi bi-calendar me-2 text-primary"></i>
                                <strong>Miembro desde:</strong>
                                <?= $profile['member_since'] ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sección derecha - Productos y descripción -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4"><i class="bi bi-shop"></i> Sobre nosotros</h3>
                        <?php if (!empty($profile['business_description'])): ?>
                            <p class="card-text">
                                <?= nl2br(htmlspecialchars($profile['business_description'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted">Este negocio aún no ha agregado una descripción.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <h3 class="mb-4"><i class="bi bi-box-seam"></i> Productos Destacados</h3>
                <hr>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="card product-card h-100 shadow">
                                <img src="../../images/<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="card-img-top product-image"
                                    alt="<?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    onerror="this.src='images/default-product.jpg'">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </h5>
                                    <p class="card-text text-muted flex-grow-1">
                                        <?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <span class="badge bg-primary">
                                            $<?= number_format($product['price'], 2) ?>
                                        </span>
                                        <span class="badge bg-secondary badge-category">
                                            <?= htmlspecialchars($product['category_name'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <button class="btn btn-outline-primary w-100 mt-auto" data-bs-toggle="modal"
                                        data-bs-target="#contactModal" onclick="setContactDetails(
                                            '<?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars($profile['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars($profile['correoelectronico'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8') ?>',
                                            <?= $product['price'] ?>
                                        )">
                                        <i class="bi bi-chat-dots"></i> Contactar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Contacto -->
    <div class="modal fade" id="contactModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contactar al vendedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="contactProduct" class="fw-bold"></h6>
                    <p id="contactPrice" class="text-muted"></p>
                    <div class="mb-3">
                        <label class="form-label">Vendedor:</label>
                        <input type="text" id="contactUsername" class="form-control" readonly>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="sendWhatsApp()">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </button>
                        <button class="btn btn-primary" onclick="sendEmail()">
                            <i class="bi bi-envelope"></i> Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentContact = null;

        function setContactDetails(username, phone, email, product, price) {
            document.getElementById("contactUsername").value = username;
            document.getElementById("contactProduct").textContent = product;
            document.getElementById("contactPrice").textContent = `Precio: $${price.toFixed(2)}`;
            currentContact = { phone, email };
        }

        function sendWhatsApp() {
            if (!currentContact?.phone) return alert('El vendedor no tiene número registrado');
            const message = `Hola ${document.getElementById("contactUsername").value}, estoy interesado en: "${document.getElementById("contactProduct").textContent}"`;
            window.open(`https://wa.me/+57${currentContact.phone}?text=${encodeURIComponent(message)}`, '_blank');
        }

        function sendEmail() {
            if (!currentContact?.email) return alert('El vendedor no tiene email registrado');
            const subject = `Interés en: ${document.getElementById("contactProduct").textContent}`;
            const body = `Hola ${document.getElementById("contactUsername").value},\n\nMe interesa tu producto: "${document.getElementById("contactProduct").textContent}"`;
            window.open(`mailto:${currentContact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`);
        }
    </script>
</body>

</html>