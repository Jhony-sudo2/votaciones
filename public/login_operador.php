<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if (logged_in_operator()) redirect('operador.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (login_locked('operator')) {
        $error = 'Demasiados intentos. Inténtalo nuevamente en unos minutos.';
    } else {
        $username = trim((string) ($_POST['usuario'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT id, nombre, password FROM operadores WHERE usuario = ? LIMIT 1');
        $stmt->execute([$username]);
        $operator = $stmt->fetch();
        if ($operator && password_verify($password, $operator['password'])) {
            login_success('operator');
            $_SESSION['operador_id'] = (int) $operator['id'];
            $_SESSION['operador_nombre'] = $operator['nombre'];
            redirect('operador.php');
        }
        login_failure('operator');
        $error = 'Credenciales incorrectas.';
    }
}
page_start('Acceso de operadores');
if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="form">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label for="usuario">Usuario</label>
    <input type="text" id="usuario" name="usuario" autocomplete="username" required autofocus>
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <button class="btn btn-primary" type="submit">Ingresar al panel</button>
</form>
<a class="back" href="/index.php">Volver al inicio</a>
<?php page_end(); ?>
