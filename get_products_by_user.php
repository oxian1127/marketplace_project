<?php
include 'db_config.php';

if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Obtener el ID del usuario basado en el nombre de usuario
    $sql = "SELECT id FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];

        // Obtener productos del usuario con ese ID
        $sql_products = "SELECT * FROM products WHERE user_id='$user_id'";
        $result_products = $conn->query($sql_products);

        if ($result_products->num_rows > 0) {
            while($row_product = $result_products->fetch_assoc()) {
                echo "<div class='product'>";
                echo "<h3>" . $row_product['title'] . "</h3>";
                echo "<p>" . $row_product['description'] . "</p>";
                echo "<p>Precio: $" . $row_product['price'] . "</p>";
                echo "<img src='images/" . $row_product['image'] . "' alt='" . $row_product['title'] . "'>";
                echo "<button onclick=\"contactSeller('$username', '".$row_product['telefono']."', '".$row_product['correoelectronico']."')\">Me interesa</button>";
                echo "</div>";
            }
        } else {
            echo "Este vendedor no tiene productos disponibles.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
} else {
    echo "Parámetro 'username' no proporcionado.";
}
?>
