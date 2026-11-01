<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $candidate = $_POST['csrf_token'] ?? '';
    if (!is_string($candidate) || !hash_equals(csrf_token(), $candidate)) {
        http_response_code(419);
        exit('Solicitud no válida o sesión caducada. Regresa e inténtalo de nuevo.');
    }
}

function redirect(string $route): never
{
    header('Location: /' . ltrim($route, '/'), true, 303);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . h($class) . '" role="status">' . h($f['message']) . '</div>';
}

function require_voter(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        redirect('login_votacion.php');
    }
}

function require_operator(): void
{
    if (!isset($_SESSION['operador_id'])) {
        redirect('login_operador.php');
    }
}

function logged_in_voter(): bool
{
    return isset($_SESSION['usuario_id']);
}

function logged_in_operator(): bool
{
    return isset($_SESSION['operador_id']);
}

// Límite básico por sesión; NO reemplaza controles por IP / WAF en producción.
function login_locked(string $scope): bool
{
    $entry = $_SESSION['login_' . $scope] ?? ['attempts' => 0, 'until' => 0];
    return (int) $entry['attempts'] >= 5 && (int) $entry['until'] > time();
}

function login_failure(string $scope): void
{
    $key = 'login_' . $scope;
    $entry = $_SESSION[$key] ?? ['attempts' => 0, 'until' => 0];
    if ((int) $entry['until'] < time()) {
        $entry = ['attempts' => 0, 'until' => 0];
    }
    $entry['attempts'] = (int) $entry['attempts'] + 1;
    $entry['until'] = time() + 300;
    $_SESSION[$key] = $entry;
}

function login_success(string $scope): void
{
    unset($_SESSION['login_' . $scope]);
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
}
