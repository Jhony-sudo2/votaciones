<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (logged_in_voter()) redirect('votacion.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login_locked('voter')) {
        $error = 'Demasiados intentos. Inténtalo nuevamente en unos minutos.';
    } else {
        $username = trim((string) ($_POST['usuario'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = db()->prepare("SELECT id, nombre_completo, password FROM usuarios WHERE usuario = ? AND estado = 'aprobado' LIMIT 1");
        $stmt->execute([$username]);
        $voter = $stmt->fetch();
        if ($voter && is_string($voter['password']) && password_verify($password, $voter['password'])) {
            login_success('voter');
            $_SESSION['usuario_id'] = (int) $voter['id'];
            $_SESSION['usuario_nombre'] = $voter['nombre_completo'];
            redirect('votacion.php');
        }
        login_failure('voter');
        $error = 'Credenciales incorrectas o registro no aprobado.';
    }
}
page_start('Acceso a votación');
if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label for="usuario">Usuario asignado</label>
    <input id="usuario" type="text" name="usuario" autocomplete="username" required autofocus>
    <label for="password">Contraseña</label>
    <input id="password" type="password" name="password" autocomplete="current-password" required>
    <button class="btn btn-primary" type="submit">Ingresar a votar</button>
</form>
<p class="note">Si todavía no tienes credenciales, primero completa tu registro y espera la validación del operador.</p>
<a class="back" href="/registro.php">Registrarme</a>
