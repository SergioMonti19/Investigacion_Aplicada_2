<?php
// ============================================================
// config.php  —  Conexión a la base de datos
// ============================================================

$host   = 'localhost';
$dbname = 'inventario_api';
$user   = 'root';
$pass   = '';            // XAMPP por defecto no tiene contraseña

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit;
}
