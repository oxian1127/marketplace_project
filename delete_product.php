<?php
include 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Verificar propiedad antes de eliminar
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Producto eliminado correctamente";
    } else {
        $_SESSION['error'] = "Error al eliminar el producto";
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit();
}
?>