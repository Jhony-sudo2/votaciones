<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_operator();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('ID de usuario no válido.');
}
$stmt = db()->prepare('SELECT foto_anverso FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$filename = $stmt->fetchColumn();
if (!is_string($filename) || basename($filename) !== $filename || !preg_match('/^[a-f0-9]{48}\.(jpg|png|webp)$/D', $filename)) {
    http_response_code(404);
    exit('Fotografía no encontrada.');
}
$path = dirname(__DIR__) . '/storage/uploads/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}
$ext = pathinfo($path, PATHINFO_EXTENSION);
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . $types[$ext]);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="documento.' . $ext . '"');
readfile($path);
