<?php
// partials/navegacion.php
// Verificar estado de la sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);


$username = $_SESSION['username'] ?? '';
$profilePic = $_SESSION['profile_pic'] ?? 'default-avatar.jpg';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <!-- Logo y marca -->
        <a class="navbar-brand" href="../../public/index.php">
            <i class="bi bi-shop me-2"></i>Marketplace
        </a>

        <!-- Botón para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Elementos de navegación -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../../app/Controllers/BusinessController.php">
                        <i class="bi bi-shop-window me-2"></i>Negocios
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../../../dashboard.php">
                            <i class="bi bi-grid me-2"></i>Dashboard
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Menú derecho -->
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <!-- Menú desplegable usuario -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <img src="../../public/profile_pics/<?= htmlspecialchars($profilePic) ?>"
                                class="rounded-circle me-2" style="width: 40px; height: 40px;"
                                onerror="this.src='profile_pics/default-avatar.jpg'">
                            <?= htmlspecialchars($username) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item"
                                    href="../../app/Controllers/profile.php?id=<?= $_SESSION['user_id'] ?>">
                                    <i class="bi bi-person-circle me-2"></i>Mi Perfil Comercial
                                </a></li>
                            <li><a class="dropdown-item" href="../../../edit_profile.php">
                                    <i class="bi bi-pencil-square me-2"></i>Editar Perfil Comercial
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="upload_product.php"><i class="bi bi-upload me-2"></i>Subir
                                    producto</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../../../logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Opciones para no logueados -->
                    <li class="nav-item">
                        <a class="nav-link" href="../../app/Controllers/AuthController.php">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../../register.php">
                            <i class="bi bi-person-plus me-2"></i>Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>