<?php
include 'app/config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

$user_id = $_SESSION['user_id'];
$image = $_POST['image'] ?? null;
$product_id = $_POST['product_id'] ?? null;

if (!$image || !$product_id) {
    die(json_encode(['success' => false, 'message' => 'Datos incompletos']));
}

try {
    // Verificar propiedad del producto
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'Producto no encontrado']));
    }

    $product = $result->fetch_assoc();
    $images = explode(",", $product['image']);

    // Eliminar imagen del array
    $updated_images = array_filter($images, function ($img) use ($image) {
        return $img !== $image;
    });

    // Actualizar base de datos
    $new_images = implode(",", $updated_images);
    $update_stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_images, $product_id);
    $update_stmt->execute();

    // Eliminar archivo físico
    $file_path = "images/" . $image;
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}