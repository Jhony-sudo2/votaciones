<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($subject) ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f3f6fa;color:#25394c;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;" aria-hidden="true">Tu registro fue aprobado. Aquí tienes tus credenciales para participar en la votación.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f6fa;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border:1px solid #dce4ed;border-radius:16px;">
                <tr><td style="padding:28px 24px;background-color:#124672;border-radius:16px 16px 0 0;">
                    <p style="margin:0 0 8px;color:#bfe2f5;font-size:12px;font-weight:bold;letter-spacing:2px;">ELECCIONES COMUNALES</p>
                    <p style="margin:0;color:#ffffff;font-size:24px;line-height:1.3;font-weight:bold;">Caserío La Libertad</p>
                </td></tr>
                <tr><td style="padding:28px 24px;">
                    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;"><?= h($greeting) ?></p>
                    <h1 style="margin:0 0 12px;color:#135b33;font-size:25px;line-height:1.3;">Tu registro fue aprobado</h1>
                    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Ya tienes tus credenciales para participar en la votación. Guárdalas en un lugar privado.</p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f6fc;border:1px solid #dce4ed;border-radius:10px;">
                        <tr><td style="padding:18px 20px 8px;">
                            <p style="margin:0 0 6px;color:#51697c;font-size:14px;">Usuario</p>
                            <p style="margin:0;font-size:22px;line-height:1.4;font-weight:bold;font-family:Consolas,'Courier New',monospace;overflow-wrap:anywhere;"><?= h($username) ?></p>
                        </td></tr>
                        <tr><td style="padding:8px 20px 18px;">
                            <p style="margin:0 0 6px;color:#51697c;font-size:14px;">Contraseña</p>
                            <p style="margin:0;font-size:20px;line-height:1.5;font-weight:bold;font-family:Consolas,'Courier New',monospace;overflow-wrap:anywhere;"><?= h($password) ?></p>
                        </td></tr>
                    </table>
                    <p style="margin:12px 0 24px;color:#51697c;font-size:14px;line-height:1.6;">Escribe la contraseña en <strong>MAYÚSCULAS</strong> y sin espacios. No incluye I, O, 0 ni 1.</p>
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                        <tr><td align="center" bgcolor="#17659a" style="border-radius:8px;">
                            <a href="<?= h($loginUrl) ?>" style="display:inline-block;padding:16px 26px;border:1px solid #17659a;border-radius:8px;color:#ffffff;font-size:17px;font-weight:bold;text-decoration:none;">Ingresar a votar</a>
                        </td></tr>
                    </table>
                    <?php if ($opening !== null): ?>
                        <p style="margin:0 0 20px;padding:14px;background-color:#e8f6ed;border-radius:8px;font-size:15px;line-height:1.6;"><strong>Apertura de votación</strong><br><?= h($opening) ?><br>Hora de Guatemala</p>
                    <?php endif; ?>
                    <p style="margin:0 0 8px;color:#51697c;font-size:13px;line-height:1.6;">Si el botón no abre, copia esta dirección en tu navegador:</p>
                    <p style="margin:0 0 24px;font-size:13px;line-height:1.6;overflow-wrap:anywhere;word-break:break-all;"><a href="<?= h($loginUrl) ?>" style="color:#17659a;text-decoration:underline;"><?= h($loginUrl) ?></a></p>
                    <p style="margin:0;padding-top:20px;border-top:1px solid #dce4ed;color:#51697c;font-size:14px;line-height:1.6;">Estas credenciales son personales. <strong>No las compartas.</strong><br>Solo puedes emitir un voto.</p>
                </td></tr>
            </table>
            <p style="margin:18px 0 0;color:#64748b;font-size:12px;line-height:1.5;">Sistema de Votación · Caserío La Libertad</p>
        </td></tr>
    </table>
</body>
</html>
