<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/registration.php';
require_once dirname(__DIR__) . '/app/voting.php';

$checks = 0;
function check(bool $condition, string $description): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($description);
    }
}

function set_window(string $opening, string $closing): void
{
    // Un espacio representa un valor vacío sin recurrir al .env local.
    putenv('REGISTRO_APERTURA=' . ($opening === '' ? ' ' : $opening));
    putenv('REGISTRO_CIERRE=' . ($closing === '' ? ' ' : $closing));
}

/** Ejecuta las páginas en procesos aislados, con sesiones en memoria y sin MySQL. */
function render_page(string $page, string $method = 'GET', array $session = [], array $post = []): array
{
    $script = '$testSession = ' . var_export($session, true) . ";\n";
    $script .= <<<'PHP'
session_set_save_handler(new class($testSession) implements SessionHandlerInterface {
    public function __construct(private array $data) {}
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false { return serialize($this->data); }
    public function write(string $id, string $data): bool { return true; }
    public function destroy(string $id): bool { return true; }
    public function gc(int $lifetime): int|false { return 0; }
});
register_shutdown_function(static function (): void {
    fwrite(STDERR, 'HTTP_STATUS=' . (http_response_code() ?: 200));
});
PHP;
    $script .= "\n" . '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ';';
    $script .= "\n" . '$_POST = ' . var_export($post, true) . ';';
    $script .= "\nrequire " . var_export(dirname(__DIR__) . '/public/' . $page, true) . ';';
    $process = proc_open(
        [PHP_BINARY, '-d', 'session.use_cookies=1', '-d', 'session.serialize_handler=php_serialize', '-r', $script],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo iniciar PHP para las pruebas de páginas.');
    }
    fclose($pipes[0]);
    $html = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    check($exitCode === 0 && preg_match('/^HTTP_STATUS=([0-9]{3})$/D', $stderr, $matches) === 1, $page . ': error de ejecución: ' . $stderr);
    return ['html' => $html, 'status' => (int) $matches[1]];
}

$keys = ['REGISTRO_APERTURA', 'REGISTRO_CIERRE', 'VOTACION_APERTURA'];
$previous = array_map('getenv', $keys);
$previousTimezone = date_default_timezone_get();
try {
    // La zona del sistema no debe modificar la interpretación del horario.
    date_default_timezone_set('UTC');
    putenv('VOTACION_APERTURA= ');
    $opening = '2027-01-10 08:00:00';
    $closing = '2027-01-10 18:00:00';
    $cases = [
        ['antes de apertura', $opening, $closing, '2027-01-10T07:59:59-06:00', 'pending'],
        ['apertura exacta', $opening, $closing, '2027-01-10T08:00:00-06:00', 'open'],
        ['último instante permitido', $opening, $closing, '2027-01-10T17:59:59.999999-06:00', 'open'],
        ['cierre exacto', $opening, $closing, '2027-01-10T18:00:00-06:00', 'closed'],
        ['después del cierre', $opening, $closing, '2027-01-11T08:00:00-06:00', 'closed'],
        ['apertura equivalente UTC', $opening, $closing, '2027-01-10T14:00:00Z', 'open'],
        ['cierre equivalente UTC', $opening, $closing, '2027-01-11T00:00:00Z', 'closed'],
        ['sin horario', '', '', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['sin apertura', '', $closing, '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['sin cierre', $opening, '', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['formato inválido', '10/01/2027 08:00', $closing, '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['fecha inexistente', '2027-02-29 08:00:00', '2027-03-01 18:00:00', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['hora inválida', $opening, '2027-01-10 25:00:00', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['cierre igual a apertura', $opening, $opening, '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['fechas invertidas', $closing, $opening, '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['año bisiesto válido', '2028-02-29 08:00:00', '2028-02-29 18:00:00', '2028-02-29T09:00:00-06:00', 'open'],
    ];
    foreach ($cases as [$label, $start, $end, $instant, $expected]) {
        set_window($start, $end);
        $window = registration_window(new DateTimeImmutable($instant));
        check($window['status'] === $expected, $label . ': estado incorrecto');
        check($window['is_open'] === ($expected === 'open'), $label . ': permiso incorrecto');
        if ($window['opens_at'] !== null) {
            check($window['opens_at']->getTimezone()->getName() === 'America/Guatemala', $label . ': zona incorrecta');
        }
    }

    $today = new DateTimeImmutable('now', new DateTimeZone('America/Guatemala'));
    $periods = [
        ['pending', $today->modify('+1 day')->format('Y-m-d H:i:s'), $today->modify('+2 days')->format('Y-m-d H:i:s')],
        ['open', $today->modify('-1 day')->format('Y-m-d H:i:s'), $today->modify('+1 day')->format('Y-m-d H:i:s')],
        ['closed', $today->modify('-2 days')->format('Y-m-d H:i:s'), $today->modify('-1 day')->format('Y-m-d H:i:s')],
        ['unconfigured', '', ''],
    ];
    foreach ($periods as [$state, $start, $end]) {
        set_window($start, $end);
        $isOpen = $state === 'open';
        $index = render_page('index.php');
        check($index['status'] === 200, $state . ': el inicio debe permanecer accesible');
        check(str_contains($index['html'], 'href="/registro.php"'), $state . ': falta el enlace de registro');
        check(str_contains($index['html'], 'href="/login_votacion.php"'), $state . ': acceso a votación ausente');
        check(str_contains($index['html'], 'datetime=') === ($state !== 'unconfigured'), $state . ': horario visible incorrecto');
        check(!str_contains($index['html'], 'Se aceptan solicitudes desde la apertura y hasta antes del cierre.'), 'El mensaje eliminado sigue presente');

        $registration = render_page('registro.php');
        check($registration['status'] === 200, $state . ': aviso de registro inaccesible');
        check(str_contains($registration['html'], 'enctype="multipart/form-data"') === $isOpen, $state . ': formulario de registro incorrecto');
        $post = render_page('registro.php', 'POST');
        check($post['status'] === ($isOpen ? 419 : 403), $state . ': POST no autorizado no rechazado');

        foreach (['login_operador.php', 'login_votacion.php'] as $page) {
            $login = render_page($page);
            check($login['status'] === 200 && str_contains($login['html'], 'name="password"'), $state . ': acceso restringido por error en ' . $page);
        }
    }

    // La apertura de votación no depende del cierre de las inscripciones.
    set_window('2027-01-01 08:00:00', '2027-01-01 18:00:00');
    $votingCases = [
        ['antes de apertura', $opening, '2027-01-10T07:59:59.999999-06:00', 'pending'],
        ['apertura exacta', $opening, '2027-01-10T08:00:00-06:00', 'open'],
        ['apertura UTC', $opening, '2027-01-10T14:00:00Z', 'open'],
        ['sin cierre al día siguiente', $opening, '2027-01-11T08:00:00-06:00', 'open'],
        ['sin cierre un año después', $opening, '2028-01-10T08:00:00-06:00', 'open'],
        ['sin configuración', '', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['fecha inválida', '2027-02-29 08:00:00', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['hora inválida', '2027-01-10 24:00:00', '2027-01-10T09:00:00-06:00', 'unconfigured'],
        ['formato inválido', '10/01/2027 08:00', '2027-01-10T09:00:00-06:00', 'unconfigured'],
    ];
    foreach ($votingCases as [$label, $start, $instant, $expected]) {
        putenv('VOTACION_APERTURA=' . ($start === '' ? ' ' : $start));
        $window = voting_window(new DateTimeImmutable($instant));
        check($window['status'] === $expected, 'Votación: ' . $label . ': estado incorrecto');
        check($window['is_open'] === ($expected === 'open'), 'Votación: ' . $label . ': permiso incorrecto');
    }

    $votingPeriods = [
        ['pending', $today->modify('+1 day')->format('Y-m-d H:i:s')],
        ['open', $today->modify('-1 day')->format('Y-m-d H:i:s')],
        ['unconfigured', ' '],
        ['unconfigured', 'no-es-una-fecha'],
    ];
    foreach ($votingPeriods as [$state, $start]) {
        putenv('VOTACION_APERTURA=' . $start);
        $index = render_page('index.php');
        check(str_contains($index['html'], 'aria-label="Horario de votación"'), 'Falta la apertura de votación en el inicio');
        if ($state === 'open') {
            check(str_contains($index['html'], 'La votación está abierta.'), 'Votación abierta no anunciada');
            continue;
        }
        $session = ['usuario_id' => 1, 'csrf_token' => 'test-token'];
        $get = render_page('votacion.php', 'GET', $session);
        check($get['status'] === 200 && !str_contains($get['html'], 'id="votacionForm"'), 'La papeleta se muestra antes de la apertura');
        $post = render_page('votacion.php', 'POST', $session, ['csrf_token' => 'test-token', 'opcion_voto' => 'opcion1']);
        check($post['status'] === 403 && !str_contains($post['html'], 'id="votacionForm"'), 'Se permite un POST directo antes de la apertura');
        check(str_contains($post['html'], 'action="/logout.php"'), 'El votante debe poder cerrar sesión');
        $login = render_page('login_operador.php');
        check($login['status'] === 200 && str_contains($login['html'], 'name="password"'), 'La apertura de votación afecta al operador');
    }
    echo $checks . " comprobaciones correctas.\n";
} finally {
    foreach ($keys as $index => $key) {
        putenv($previous[$index] === false ? $key : $key . '=' . $previous[$index]);
    }
    date_default_timezone_set($previousTimezone);
}
