<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $dpi = trim((string) ($_POST['dpi'] ?? ''));
    $nombre = trim((string) ($_POST['nombre_completo'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $file = $_FILES['foto_anverso'] ?? null;

    if (!preg_match('/^[0-9]{13}$/D', $dpi)) {
        $error = 'El DPI debe tener exactamente 13 dígitos.';
    } elseif (strlen($nombre) < 4 || strlen($nombre) > 255) {
        $error = 'Ingresa un nombre completo válido (4 a 255 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Ingresa un correo electrónico válido.';
    } elseif (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Selecciona una fotografía del anverso del DPI.';
    } elseif ((int) $file['size'] > 5 * 1024 * 1024 || (int) $file['size'] === 0) {
        $error = 'La imagen debe tener entre 1 byte y 5 MB.';
    } else {
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $dimensions = @getimagesize($file['tmp_name']);
        if (!isset($types[$mime]) || $dimensions === false || !in_array($dimensions[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $error = 'Formato no permitido. Utiliza JPG, PNG o WEBP auténtico.';
        } else {
            $fileName = bin2hex(random_bytes(24)) . '.' . $types[$mime];
            $base = dirname(__DIR__) . '/storage/uploads';
            if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
                $error = 'No se pudo preparar el almacenamiento de imágenes.';
            } elseif (!move_uploaded_file($file['tmp_name'], $base . '/' . $fileName)) {
                $error = 'No se pudo guardar la imagen.';
            } else {
                try {
                    $query = db()->prepare('INSERT INTO usuarios (dpi, nombre_completo, email, foto_anverso) VALUES (?, ?, ?, ?)');
                    $query->execute([$dpi, $nombre, $email, $fileName]);
                    set_flash('success', 'Registro enviado. Un operador validará tu información.');
                    redirect('registro.php');
                } catch (PDOException $exception) {
                    @unlink($base . '/' . $fileName);
                    if ($exception->getCode() === '23000') {
                        $error = 'El DPI o correo electrónico ya está registrado.';
                    } else {
                        error_log('Error al registrar: ' . $exception->getMessage());
                        $error = 'No fue posible registrar la solicitud.';
                    }
                }
            }
        }
    }
}
page_start('Registro de votante');
show_flash();
if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= h($error) ?></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="form" autocomplete="on">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label for="dpi">DPI (13 dígitos)</label>
    <input id="dpi" name="dpi" type="text" inputmode="numeric" maxlength="13" pattern="[0-9]{13}" required value="<?= h($_POST['dpi'] ?? '') ?>">
    <label for="nombre_completo">Nombre completo</label>
    <input id="nombre_completo" name="nombre_completo" type="text" maxlength="255" required value="<?= h($_POST['nombre_completo'] ?? '') ?>">
    <label for="email">Correo electrónico</label>
    <input id="email" name="email" type="email" maxlength="255" required value="<?= h($_POST['email'] ?? '') ?>">
    <label for="foto_anverso">Fotografía del anverso del DPI</label>
    <input id="foto_anverso" name="foto_anverso" type="file" accept="image/jpeg,image/png,image/webp" required>
    <p class="hint">JPG, PNG o WEBP · Máximo 5 MB</p>
    <button class="btn btn-primary" type="submit">Enviar registro</button>
</form>
<a class="back" href="/index.php">Volver al inicio</a>
