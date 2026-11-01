<?php
declare(strict_types=1);

/** Retorna true cuando pudo enviarse. Sin SMTP configurado, no intenta enviar. */
function send_credentials(string $to, string $username, string $temporaryPassword): bool
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
        $mail->Subject = 'Credenciales temporales para la votación';
        $mail->Body = "Su registro fue aprobado.\nUsuario: {$username}\nContraseña temporal: {$temporaryPassword}\nIngrese solamente en la dirección oficial comunicada por el operador.\n";
        $mail->send();
        return true;
    } catch (Throwable $exception) {
        error_log('Error al enviar notificación SMTP: ' . $exception->getMessage());
        return false;
    }
}
