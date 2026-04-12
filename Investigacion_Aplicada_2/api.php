<?php
// ============================================================
// api.php  —  API REST: Inventario de Productos
// Autor:  Sergio (Backend)
// Métodos: GET · POST · PUT · DELETE
// ============================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Pre-flight CORS (el navegador lo envía antes de POST/PUT/DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';   // Importa $pdo

// -----------------------------------------------------------
// Función auxiliar: enviar respuesta JSON y terminar
// -----------------------------------------------------------
function responder(int $codigo, array $cuerpo): void {
    http_response_code($codigo);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -----------------------------------------------------------
// Leer ID desde la URL  →  api.php?id=5
// -----------------------------------------------------------
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
$metodo = $_SERVER['REQUEST_METHOD'];

// -----------------------------------------------------------
// ENRUTADOR principal
// -----------------------------------------------------------
switch ($metodo) {

    // ========================================================
    // GET — Consultar productos
    //   GET api.php        → lista completa  (200)
    //   GET api.php?id=3   → un producto     (200 ó 404)
    // ========================================================
    case 'GET':

        if ($id !== null) {
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $producto = $stmt->fetch();

            if ($producto) {
                responder(200, ['status' => 'success', 'data' => $producto]);
            } else {
                responder(404, [
                    'status'  => 'error',
                    'message' => "Producto con id=$id no encontrado"
                ]);
            }

        } else {
            $stmt      = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
            $productos = $stmt->fetchAll();

            responder(200, [
                'status' => 'success',
                'total'  => count($productos),
                'data'   => $productos
            ]);
        }
        break;

    // ========================================================
    // POST — Insertar un nuevo producto  (201 ó 400)
    // Body JSON:
    // {
    //   "nombre":      "Laptop",
    //   "precio":      599.99,
    //   "cantidad":    10,
    //   "descripcion": "Opcional"
    // }
    // ========================================================
    case 'POST':

        $datos = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            responder(400, ['status' => 'error', 'message' => 'JSON no válido en el cuerpo']);
        }

        // Validar campos obligatorios
        $faltantes = [];
        foreach (['nombre', 'precio', 'cantidad'] as $campo) {
            if (!isset($datos[$campo]) || $datos[$campo] === '') {
                $faltantes[] = $campo;
            }
        }
        if (!empty($faltantes)) {
            responder(400, [
                'status'    => 'error',
                'message'   => 'Faltan campos obligatorios',
                'faltantes' => $faltantes
            ]);
        }

        if (!is_numeric($datos['precio']) || (float)$datos['precio'] < 0) {
            responder(400, ['status' => 'error', 'message' => '"precio" debe ser un número positivo']);
        }
        if (!is_numeric($datos['cantidad']) || (int)$datos['cantidad'] < 0) {
            responder(400, ['status' => 'error', 'message' => '"cantidad" debe ser un entero positivo']);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO productos (nombre, precio, cantidad, descripcion)
             VALUES (:nombre, :precio, :cantidad, :descripcion)"
        );
        $stmt->execute([
            ':nombre'      => trim($datos['nombre']),
            ':precio'      => (float) $datos['precio'],
            ':cantidad'    => (int)   $datos['cantidad'],
            ':descripcion' => trim($datos['descripcion'] ?? '')
        ]);

        responder(201, [
            'status'  => 'success',
            'message' => 'Producto creado exitosamente',
            'data'    => ['id' => (int) $pdo->lastInsertId()]
        ]);
        break;

    // ========================================================
    // PUT — Actualizar producto existente  (200, 400 ó 404)
    //   PUT api.php?id=3
    // Body JSON: los campos que se quieran modificar
    // ========================================================
    case 'PUT':

        if ($id === null) {
            responder(400, [
                'status'  => 'error',
                'message' => 'Se requiere ?id= para actualizar'
            ]);
        }

        // Verificar que existe
        $check = $pdo->prepare("SELECT id FROM productos WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            responder(404, [
                'status'  => 'error',
                'message' => "Producto con id=$id no encontrado"
            ]);
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            responder(400, ['status' => 'error', 'message' => 'JSON no válido en el cuerpo']);
        }

        // Construir dinámicamente solo los campos recibidos
        $campos     = [];
        $parametros = [':id' => $id];

        if (isset($datos['nombre']) && $datos['nombre'] !== '') {
            $campos[] = 'nombre = :nombre';
            $parametros[':nombre'] = trim($datos['nombre']);
        }
        if (isset($datos['precio']) && is_numeric($datos['precio'])) {
            $campos[] = 'precio = :precio';
            $parametros[':precio'] = (float) $datos['precio'];
        }
        if (isset($datos['cantidad']) && is_numeric($datos['cantidad'])) {
            $campos[] = 'cantidad = :cantidad';
            $parametros[':cantidad'] = (int) $datos['cantidad'];
        }
        if (isset($datos['descripcion'])) {
            $campos[] = 'descripcion = :descripcion';
            $parametros[':descripcion'] = trim($datos['descripcion']);
        }

        if (empty($campos)) {
            responder(400, [
                'status'  => 'error',
                'message' => 'No se enviaron campos válidos para actualizar'
            ]);
        }

        $sql = "UPDATE productos SET " . implode(', ', $campos) . " WHERE id = :id";
        $pdo->prepare($sql)->execute($parametros);

        responder(200, [
            'status'  => 'success',
            'message' => "Producto id=$id actualizado correctamente"
        ]);
        break;

    // ========================================================
    // DELETE — Eliminar un producto  (200 ó 404)
    //   DELETE api.php?id=3
    // ========================================================
    case 'DELETE':

        if ($id === null) {
            responder(400, [
                'status'  => 'error',
                'message' => 'Se requiere ?id= para eliminar'
            ]);
        }

        $check = $pdo->prepare("SELECT id FROM productos WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            responder(404, [
                'status'  => 'error',
                'message' => "Producto con id=$id no encontrado"
            ]);
        }

        $pdo->prepare("DELETE FROM productos WHERE id = :id")->execute([':id' => $id]);

        responder(200, [
            'status'  => 'success',
            'message' => "Producto id=$id eliminado correctamente"
        ]);
        break;

    // ========================================================
    // Cualquier otro método  →  405 Method Not Allowed
    // ========================================================
    default:
        responder(405, [
            'status'          => 'error',
            'message'         => "Método '$metodo' no permitido",
            'metodos_validos' => ['GET', 'POST', 'PUT', 'DELETE']
        ]);
        break;
}
