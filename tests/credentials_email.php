<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Configuración aislada: estas pruebas no leen .env ni conectan a SMTP/MySQL.
$testConfig = [];
function env_value(string $key, string $default = ''): string
{
    global $testConfig;
    return $testConfig[$key] ?? $default;
}

require_once dirname(__DIR__) . '/app/helpers.php';
require_once dirname(__DIR__) . '/app/credentials.php';
require_once dirname(__DIR__) . '/app/voting.php';
require_once dirname(__DIR__) . '/app/mailer.php';

$checks = 0;
function check(bool $condition, string $description): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($description);
    }
}

foreach ([1, 2, 25, PHP_INT_MAX] as $id) {
    check(voter_username($id) === 'votante_' . $id, 'El usuario no corresponde al número de registro');
}
foreach ([0, -1] as $id) {
    $rejected = false;
    try {
        voter_username($id);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    check($rejected, 'Se aceptó un número de registro inválido');
}

$passwords = [];
for ($i = 0; $i < 64; $i++) {
    $passwords[] = $password = voter_password();
    check(preg_match('/^[A-HJ-NP-Z2-9]{16}$/D', $password) === 1, 'La contraseña contiene caracteres confusos o una longitud incorrecta');
}
check(count(array_unique($passwords)) === count($passwords), 'Se generaron contraseñas repetidas en la muestra');
$hash = password_hash($passwords[0], PASSWORD_DEFAULT);
check(password_verify($passwords[0], $hash), 'La contraseña nueva no funciona con la verificación del login');
check(!password_verify($passwords[0] . 'X', $hash), 'Una contraseña incorrecta fue aceptada');

$officialUrl = 'https://votacioneslibertad-dehje4gfd6crc2e9.mexicocentral-01.azurewebsites.net';
check(credentials_login_url() === $officialUrl . '/login_votacion.php', 'El sitio oficial predeterminado no es correcto');
$testConfig['APP_URL'] = $officialUrl . '/';
check(credentials_login_url() === $officialUrl . '/login_votacion.php', 'La barra final produce una URL incorrecta');
$testConfig['VOTACION_APERTURA'] = '2027-11-15 14:00:00';
$testName = 'María <script>alert("x")</script> & Ana';
$testPassword = 'K7MX4P9RH6TB8W2N';
$bufferLevel = ob_get_level();
$message = build_credentials_email('votante_2', $testPassword, $testName);
check(ob_get_level() === $bufferLevel, 'La plantilla dejó un buffer de salida abierto');
foreach (['html', 'text'] as $format) {
    check(str_contains($message[$format], 'votante_2'), 'Falta el usuario en ' . $format);
    check(str_contains($message[$format], $testPassword), 'Falta la contraseña en ' . $format);
    check(str_contains($message[$format], '15/11/2027 14:00:00'), 'La apertura no se muestra en hora de Guatemala en ' . $format);
    check(str_contains($message[$format], $officialUrl . '/login_votacion.php'), 'El acceso no usa el sitio oficial en ' . $format);
    check(!str_contains($message[$format], 'Cierre:'), 'Se añadió un cierre de votación al correo');
}
check(!str_contains($message['subject'], $testPassword), 'La contraseña aparece en el asunto');
check(str_contains($message['html'], '&lt;script&gt;'), 'No se escapó el nombre del votante');
check(str_contains($message['text'], $testName), 'La versión de texto plano contiene entidades HTML');

$document = new DOMDocument();
$previousErrors = libxml_use_internal_errors(true);
try {
    $document->loadHTML($message['html']);
} finally {
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
}
check($document->getElementsByTagName('script')->length === 0, 'El nombre inyectó un script');
check($document->getElementsByTagName('form')->length === 0, 'El correo contiene un formulario de acceso');
check($document->getElementsByTagName('a')->length === 2, 'Falta el botón o la dirección de respaldo');
foreach ($document->getElementsByTagName('a') as $link) {
    check($link->getAttribute('href') === $officialUrl . '/login_votacion.php', 'El enlace contiene datos de acceso u otra dirección');
}

unset($testConfig['VOTACION_APERTURA']);
$withoutOpening = build_credentials_email('votante_1', $testPassword);
check(str_contains($withoutOpening['html'], 'Hola:'), 'El correo sin nombre no tiene saludo');
check(!str_contains($withoutOpening['html'], 'Apertura de votación'), 'Se muestra una fecha de apertura inexistente');
check(!str_contains($withoutOpening['text'], 'Apertura de votación'), 'El texto plano inventa la apertura');
$testConfig['VOTACION_APERTURA'] = 'fecha inválida';
$invalidOpening = build_credentials_email('votante_1', $testPassword);
check(!str_contains($invalidOpening['html'], 'Apertura de votación'), 'Se muestra una apertura inválida');

$testConfig['APP_URL'] = 'https://elecciones.example';
check(credentials_login_url() === 'https://elecciones.example/login_votacion.php', 'No se puede configurar el sitio oficial');
foreach (['http://elecciones.example', 'javascript:alert(1)', 'https://usuario:clave@elecciones.example', 'https://elecciones.example?clave=123', 'https://elecciones.example#seccion'] as $invalidUrl) {
    $testConfig['APP_URL'] = $invalidUrl;
    $rejected = false;
    try {
        build_credentials_email('votante_1', $testPassword);
    } catch (RuntimeException) {
        $rejected = true;
    }
    check($rejected, 'Se aceptó una dirección de acceso no válida');
}

echo $checks . " comprobaciones correctas. No se enviaron correos.\n";
