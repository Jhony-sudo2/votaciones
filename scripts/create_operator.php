<?php
declare(strict_types=1);

// Se ejecuta automáticamente al arrancar el contenedor web.
require_once dirname(__DIR__) . '/app/database.php';

$username = env_value('ADMIN_USER');
$password = env_value('ADMIN_PASSWORD');
if ($username === '' || strlen($username) > 100 || strlen($password) < 16) {
    fwrite(STDERR, "ERROR: configura ADMIN_USER y ADMIN_PASSWORD de al menos 16 caracteres en .env\n");
    exit(1);
}
try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM operadores WHERE usuario = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn()) {
        echo "El operador inicial ya existe, se conserva su contraseña.\n";
        exit(0);
    }
    $stmt = $pdo->prepare('INSERT INTO operadores (usuario, password, nombre) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'Administrador']);
    echo "Operador inicial creado. No se muestra ni registra la contraseña.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR al crear operador: ' . $exception->getMessage() . "\n");
    exit(1);
}
