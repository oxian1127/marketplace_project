<?php
include '../app/config/database.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'marketplace');
define('ITEMS_PER_PAGE', 12);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

session_start();

$isLoggedIn = false;
$username = "";
$userId = null;
$profilePic = 'default-avatar.jpg';
$numProducts = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultUser = $stmt->get_result();

    if ($resultUser->num_rows > 0) {
        $rowUser = $resultUser->fetch_assoc();
        $isLoggedIn = true;
        $username = $rowUser['username'];
        $profilePic = $rowUser['profile_pic'] ?? 'default-avatar.jpg';

        // Obtener número de productos
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
        $stmtCount->bind_param("i", $userId);
        $stmtCount->execute();
        $numProducts = $stmtCount->get_result()->fetch_row()[0];
        $stmtCount->close();
    }
    $stmt->close();
}

// Parámetros de búsqueda
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$seller_filter = filter_input(INPUT_GET, 'seller', FILTER_VALIDATE_INT) ?? 0;
$category_filter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?? 0;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

// Construir consulta principal
$sql = "SELECT p.*, u.username AS seller_username, c.name AS category_name 
        FROM products p
        INNER JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($seller_filter > 0) {
    $sql .= " AND p.user_id = ?";
    $params[] = $seller_filter;
    $types .= 'i';
}

if ($category_filter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

// Paginación
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * ITEMS_PER_PAGE;
$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Ejecutar consulta
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Error en la consulta: " . $conn->error);
}

// Calcular total de páginas
$countQuery = "SELECT COUNT(*) as total 
              FROM products p
              INNER JOIN users u ON p.user_id = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE 1=1";

$countParams = [];
$countTypes = '';

if (!empty($search)) {
    $countQuery .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes .= 'ss';
}

if ($seller_filter > 0) {
    $countQuery .= " AND p.user_id = ?";
    $countParams[] = $seller_filter;
    $countTypes .= 'i';
}

if ($category_filter > 0) {
    $countQuery .= " AND p.category_id = ?";
    $countParams[] = $category_filter;
    $countTypes .= 'i';
}

$countStmt = $conn->prepare($countQuery);
if ($countStmt) {
    if (!empty($countTypes)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalProducts = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalProducts / ITEMS_PER_PAGE);
} else {
    $totalPages = 1;
}

// Obtener filtros
$sellers = $conn->query("SELECT DISTINCT u.id, u.username 
                       FROM users u
                       INNER JOIN products p ON u.id = p.user_id
                       ORDER BY u.username");

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

// Función para obtener detalles del usuario
function getUserDetails($user_id, $conn)
{
    $stmt = $conn->prepare("SELECT username, telefono, correoelectronico, profile_pic 
                          FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .product-card {
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .filter-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }

        .product-date {
            font-size: 0.85rem;
            border-right: 1px solid #dee2e6;
            padding-right: 0.8rem;
            margin-right: 0.8rem;
        }
    </style>

    <!--Estilos presentacion-comercio-->
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #2980b9 100%);
        }

        .presentacion-comercio {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.15);
            border-bottom: 3px solid #f1c40f;
        }

        .btn-warning {
            transition: all 0.2s;
            border: 1px solid #ffffff;
            font-weight: 500;
            font-size: 1.1rem;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(241, 196, 15, 0.2);
        }

        @media (max-width: 768px) {
            .display-5 {
                font-size: 2rem !important;
            }

            .fs-5 {
                font-size: 1rem !important;
            }
        }

        .btn-close {
            opacity: 0.7;
            transition: all 0.3s;
            transform: scale(0.9);
        }

        .btn-close:hover {
            opacity: 1;
            transform: scale(1);
        }

        .hidden-banner {
            display: none !important;
        }

        .btn-close-custom {
            opacity: 0.8;
            transition: all 0.3s;
            filter: invert(27%) sepia(51%) saturate(2878%) hue-rotate(187deg) brightness(104%) contrast(97%);
            padding: 0.5rem;
            background-size: 1.2em;
        }

        .btn-close-custom:hover {
            opacity: 1;
            filter: invert(13%) sepia(98%) saturate(5830%) hue-rotate(245deg) brightness(85%) contrast(101%);
        }

        .btn-outline-primary {
            border-color: #2c3e50;
            color: #2c3e50;
        }

        .btn-outline-primary:hover {
            background-color: #2c3e50;
            border-color: #2c3e50;
            color: white;
        }
    </style>

</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-shop"></i> Marketplace</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                                data-bs-toggle="dropdown">
                                <img src="profile_pics/<?= htmlspecialchars($profilePic) ?>" class="rounded-circle me-2"
                                    style="width: 40px; height: 40px;" onerror="this.src='profile_pics/default-avatar.jpg'">
                                <?= htmlspecialchars($username) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item fw-bold">Publicado(s) <?= $numProducts ?></span></li>
                                <li><a class="dropdown-item" href="../upload_product.php"><i
                                            class="bi bi-upload me-2"></i>Subir producto</a></li>
                                <li><a class="dropdown-item" href="../dashboard.php"><i
                                            class="bi bi-boxes me-2"></i>Administrar productos</a></li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="../edit_user.php"><i
                                            class="bi bi-person-gear me-2"></i>Editar Perfil</a></li>
                                <li><a class="dropdown-item"
                                        href="../app/Controllers/profile.php?id=<?= $_SESSION['user_id'] ?>">
                                        <i class="bi bi-person-circle me-2"></i>Mi Tienda
                                    </a></li>


                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <a class="dropdown-item" href="../app/Controllers/BusinessController.php">
                                        <i class="bi bi-shop-window me-2"></i>Negocios
                                    </a>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="../../logout.php"><i
                                            class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../app/Controllers/AuthController.php"><i
                                    class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="../register.php"><i
                                    class="bi bi-person-plus me-1"></i>Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Filtros -->
        <div class="filter-container mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar productos..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="seller" class="form-select">
                        <option value="0">Todos los vendedores</option>
                        <?php while ($seller = $sellers->fetch_assoc()): ?>
                            <option value="<?= $seller['id'] ?>" <?= $seller_filter == $seller['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($seller['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="0">Todas las categorías</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Aplicar filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- presentacion index.php -->
        <section class="presentacion-comercio mb-4" id="bannerComercio">
            <div class="container-fluid py-4 bg-gradient-primary position-relative">
                <button id="toggleBanner"
                    class="btn btn-close btn-close-white position-absolute top-0 end-0 m-3"></button>
                <div class="container text-center py-4">
                    <h1 class="display-5 fw-bold text-white mb-3 animate__animated animate__fadeInDown">
                        <span class="text-warning">¡</span>El comercio local
                        <span class="text-warning">crece</span>
                        cuando nos<br>
                        <span class="text-warning">conectamos</span>
                        <span class="text-warning">!</span>
                    </h1>
                    <p class="text-light mb-3 fs-5">Tu plataforma para comprar, vender y colaborar</p>
                    <a href="../app/Controllers/BusinessController.php" class="btn btn-warning shadow-sm px-4 py-2">
                        <i class="bi bi-compass me-2"></i>Descubrir Ahora
                    </a>
                </div>
            </div>
        </section>

        <h5 style="text-transform: uppercase;" class="text-center mb-5 display-6 fw-bold text-primary">
            Todos los productos
            <hr>
        </h5>

        <!-- Productos -->
        <div class="row g-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <?php $seller = getUserDetails($product['user_id'], $conn); ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100 shadow">
                            <img src="../images/<?= htmlspecialchars($product['image']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($product['title']) ?>"
                                onerror="this.src='../images/default-product.jpg'">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($product['title']) ?>
                                </h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($product['description']) ?></p>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-currency-dollar"></i> <?= number_format($product['price'], 2) ?>
                                    </span>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
                                    </span>
                                </div>

                                <!--bloque vendedor-->
                                <div class="d-flex align-items-center mb-3">
                                    <small class="text-muted product-date">
                                        <i class="bi bi-clock-history"></i>
                                        <?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>
                                    </small>
                                    <a href="../app/Controllers/profile.php?id=<?= $product['user_id'] ?>"
                                        class="text-decoration-none">
                                        <img src="profile_pics/<?= htmlspecialchars($seller['profile_pic'] ?? 'default-avatar.jpg') ?>"
                                            class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;"
                                            alt="Avatar" onerror="this.src='../images/default-avatar.jpg'">
                                        <small class="text-muted">
                                            <?= htmlspecialchars($seller['username']) ?>
                                        </small>
                                    </a>
                                </div>
                                <!--boton contactar vendedor-->
                                <button class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                                    data-bs-target="#contactModal" onclick="setContactDetails(
                                            '<?= htmlspecialchars($seller['username']) ?>',
                                            '<?= htmlspecialchars($seller['telefono'] ?? '') ?>',
                                            '<?= htmlspecialchars($seller['correoelectronico'] ?? '') ?>',
                                            '<?= htmlspecialchars($product['title']) ?>',
                                            <?= $product['price'] ?>
                                        )">
                                    <i class="bi bi-chat-dots"></i> Contactar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> No se encontraron productos
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

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
            currentContact = {
                phone,
                email
            };
        }

        function sendWhatsApp() {
            if (!currentContact?.phone) return alert('El vendedor no tiene número registrado');
            const message =
                `Hola ${document.getElementById("contactUsername").value}, estoy interesado en: "${document.getElementById("contactProduct").textContent}"`;
            window.open(`https://wa.me/+57${currentContact.phone}?text=${encodeURIComponent(message)}`, '_blank');
        }

        function sendEmail() {
            if (!currentContact?.email) return alert('El vendedor no tiene email registrado');
            const subject = `Interés en: ${document.getElementById("contactProduct").textContent}`;
            const body =
                `Hola ${document.getElementById("contactUsername").value},\n\nMe interesa tu producto: "${document.getElementById("contactProduct").textContent}"`;
            window.open(
                `mailto:${currentContact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
            );
        }
    </script>

    <!--escript para ocultar mensaje de bienvenida-->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const banner = document.getElementById('bannerComercio');
            const toggleButton = document.getElementById('toggleBanner');

            // Verificar preferencia guardada
            const bannerState = localStorage.getItem('bannerVisible');
            if (bannerState === 'hidden') {
                banner.classList.add('hidden-banner');
            }

            // Manejar clic en el botón
            toggleButton.addEventListener('click', function () {
                if (banner.classList.contains('hidden-banner')) {
                    banner.classList.remove('hidden-banner');
                    localStorage.setItem('bannerVisible', 'visible');
                } else {
                    banner.classList.add('hidden-banner');
                    localStorage.setItem('bannerVisible', 'hidden');
                }
            });

            // Opcional: Botón para recuperar el banner
            const showBannerButton = document.createElement('button');
            showBannerButton.innerHTML = '<i class="bi bi-megaphone me-2"></i>Mostrar mensaje';
            showBannerButton.className = 'btn btn-sm btn-outline-primary position-fixed bottom-0 end-0 m-3';
            showBannerButton.style.display = bannerState === 'hidden' ? 'block' : 'none';

            showBannerButton.addEventListener('click', function () {
                banner.classList.remove('hidden-banner');
                localStorage.setItem('bannerVisible', 'visible');
                this.style.display = 'none';
            });

            document.body.appendChild(showBannerButton);
        });
    </script>

</body>

</html>