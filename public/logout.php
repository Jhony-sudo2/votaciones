<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    redirect('index.php');
}
page_start('Cerrar sesión');
?>
<p>¿Deseas terminar tu sesión?</p>
<form action="/logout.php" method="post" class="form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button type="submit" class="btn btn-primary">Sí, cerrar sesión</button></form>
<a class="back" href="/index.php">Volver al inicio</a>
<?php page_end(); ?>
