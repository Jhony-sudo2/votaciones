<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    // Permite arrancar con PHP local sin Docker (el .env está fuera de public/).
    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$fileKey, $fileValue] = explode('=', $line, 2);
                $fileKey = trim($fileKey);
                if (preg_match('/^[A-Z][A-Z0-9_]*$/D', $fileKey)) {
                    $fileValues[$fileKey] = trim(trim($fileValue), '"\'');
                }
            }
        }
    }
    return ($fileValues[$key] ?? '') !== '' ? $fileValues[$key] : $default;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = env_value('DB_HOST', '127.0.0.1');
    $port = env_value('DB_PORT', '3306');
    $name = env_value('DB_NAME', 'sistema_votacion');
    $user = env_value('DB_USER', 'votacion_user');
    $pass = env_value('DB_PASSWORD');

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // En Azure: DB_SSL=true. En local con Docker: no se define.
    if (in_array(strtolower(env_value('DB_SSL', 'false')), ['1', 'true', 'yes'], true)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = env_value('DB_SSL_CA', '/etc/ssl/certs/ca-certificates.crt');
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            $options
        );
    } catch (PDOException $exception) {
        error_log('Error de conexión a BD: ' . $exception->getMessage());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "No se pudo conectar con la base de datos.\n");
            exit(1);
        }
        http_response_code(503);
        exit('No se pudo conectar con la base de datos. Revisa la configuración y los contenedores.');
    }
    return $pdo;
}
