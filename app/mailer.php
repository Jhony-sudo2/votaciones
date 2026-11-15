<?php
declare(strict_types=1);

function credentials_login_url(): string
{
    $baseUrl = rtrim(trim(env_value('APP_URL', 'https://votacioneslibertad-dehje4gfd6crc2e9.mexicocentral-01.azurewebsites.net')), '/');
    $parts = parse_url($baseUrl);
    if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !is_array($parts)
        || strtolower($parts['scheme'] ?? '') !== 'https'
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('APP_URL debe ser una dirección HTTPS, sin usuario, contraseña, parámetros ni fragmento.');
    }
    return $baseUrl . '/login_votacion.php';
}

/** Construye ambas versiones sin conectarse a SMTP ni guardar las credenciales. */
function build_credentials_email(string $username, string $password, string $fullName = ''): array
{
    $loginUrl = credentials_login_url();
    $greeting = trim($fullName) === '' ? 'Hola:' : 'Hola, ' . trim($fullName) . ':';
    $window = voting_window();
    $opening = $window['opens_at']?->format('d/m/Y H:i:s');
    $subject = 'Tus credenciales para votar · Caserío La Libertad';
    $text = "Elecciones Comunales · Caserío La Libertad\n\n{$greeting}\n\nTu registro fue aprobado.\nYa tienes tus credenciales para participar en la votación.\n\nUsuario: {$username}\nContraseña: {$password}\n\nEscribe la contraseña en MAYÚSCULAS y sin espacios. No incluye I, O, 0 ni 1.\n\nIngresar a votar:\n{$loginUrl}\n";
    if ($opening !== null) {
        $text .= "\nApertura de votación: {$opening} (hora de Guatemala).\n";
    }
    $text .= "\nEstas credenciales son personales. No las compartas.\nSolo puedes emitir un voto.\n";

    ob_start();
    try {
        require __DIR__ . '/templates/credentials_email.php';
        $html = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
    return ['subject' => $subject, 'html' => $html, 'text' => $text];
}

/** Retorna true cuando pudo enviarse. Sin SMTP configurado, no intenta enviar. */
function send_credentials(string $to, string $username, string $temporaryPassword, string $fullName = ''): bool
{
    $host = env_value('SMTP_HOST');
    $sender = env_value('SMTP_FROM');
    if ($host === '' || $sender === '') {
        return false;
    }
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('PHPMailer no instalado. Ejecuta composer install.');
        return false;
    }
    require_once $autoload;
    try {
        $message = build_credentials_email($username, $temporaryPassword, $fullName);
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) env_value('SMTP_PORT', '587');
        $mail->SMTPAuth = true;
        $mail->Username = env_value('SMTP_USER');
        $mail->Password = env_value('SMTP_PASSWORD');
        $mail->SMTPSecure = env_value('SMTP_ENCRYPTION', 'tls') === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($sender, 'Sistema de Votación · Caserío La Libertad');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $message['subject'];
        $mail->Body = $message['html'];
        $mail->AltBody = $message['text'];
        $mail->send();
        return true;
    } catch (Throwable $exception) {
        error_log('Error al enviar notificación SMTP: ' . $exception->getMessage());
        return false;
    }
}
