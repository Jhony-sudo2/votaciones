<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_operator();
require_once dirname(__DIR__) . '/app/mailer.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = (string) ($_POST['accion'] ?? '');
    if (!$id || !in_array($action, ['aprobar', 'rechazar'], true)) {
        $error = 'Solicitud no válida.';
    } else {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, email, estado FROM usuarios WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $candidate = $stmt->fetch();
            if (!$candidate || $candidate['estado'] !== 'pendiente') {
                $pdo->rollBack();
                $error = 'La solicitud ya fue procesada o no existe.';
            } elseif ($action === 'rechazar') {
                $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'rechazado' WHERE id = ? AND estado = 'pendiente'");
                $stmt->execute([$id]);
                $pdo->commit();
                set_flash('success', 'Registro rechazado.');
                redirect('operador.php');
            } else {
                $username = 'votante_' . bin2hex(random_bytes(7));
                $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#';
                $password = '';
                for ($i = 0; $i < 18; $i++) {
                    $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET estado='aprobado', usuario=?, password=? WHERE id=? AND estado='pendiente'");
                $stmt->execute([$username, $hash, $id]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('La aprobación no modificó el registro esperado.');
                }
                $pdo->commit();
                $sent = send_credentials($candidate['email'], $username, $password);
                // Se muestran una sola vez después de aprobar; no se guardan en logs.
                $_SESSION['nuevas_credenciales'] = [
                    'usuario' => $username,
                    'password' => $password,
                    'email' => $candidate['email'],
                    'enviado' => $sent,
                ];
                set_flash('success', $sent
                    ? 'Votante aprobado. Se enviaron las credenciales por correo.'
                    : 'Votante aprobado. El correo no está configurado o falló; entrega las credenciales de forma segura.');
                redirect('operador.php');
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Error al gestionar votante: ' . $exception->getMessage());
            $error = 'No se pudo completar la operación.';
        }
    }
}

$stmt = db()->query("SELECT id, dpi, nombre_completo, email, fecha_registro FROM usuarios WHERE estado = 'pendiente' ORDER BY fecha_registro ASC, id ASC");
$pending = $stmt->fetchAll();
page_start('Panel de Operadores', 'Validación de personas registradas');
?>
<p class="user-info">Operador: <strong><?= h($_SESSION['operador_nombre'] ?? '') ?></strong></p>
<nav class="topnav"><a class="btn btn-secondary" href="/dashboard.php">Ver resultados</a><a class="btn btn-outline" href="/index.php">Inicio</a></nav>
<?php show_flash(); ?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if (isset($_SESSION['nuevas_credenciales'])):
    $cred = $_SESSION['nuevas_credenciales'];
    unset($_SESSION['nuevas_credenciales']); ?>
    <section class="credentials" aria-label="Credenciales generadas">
        <h2>Credenciales generadas (se muestran una sola vez)</h2>
        <p>Correo del votante: <strong><?= h($cred['email']) ?></strong></p>
        <p>Usuario: <code><?= h($cred['usuario']) ?></code></p>
        <p>Contraseña: <code><?= h($cred['password']) ?></code></p>
        <p class="note"><?= $cred['enviado'] ? 'Correo enviado; esta visualización es de respaldo.' : 'Correo no enviado: entrega la contraseña por un canal privado.' ?></p>
    </section>
<?php endif; ?>
<h2 class="section-title">Solicitudes pendientes: <?= count($pending) ?></h2>
<?php if (!$pending): ?>
    <p class="empty">No hay solicitudes pendientes de validación.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>DPI</th><th>Nombre</th><th>Correo</th><th>Documento</th><th>Fecha</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $v): ?>
        <tr>
            <td><?= h($v['dpi']) ?></td>
            <td><?= h($v['nombre_completo']) ?></td>
            <td><?= h($v['email']) ?></td>
            <td><a href="/foto.php?id=<?= (int) $v['id'] ?>" target="_blank" rel="noopener">Ver DPI</a></td>
            <td><?= h($v['fecha_registro']) ?></td>
            <td class="table-actions">
                <form method="post" data-confirm="¿Aprobar este registro?">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="usuario_id" value="<?= (int) $v['id'] ?>">
                    <input type="hidden" name="accion" value="aprobar">
                    <button class="btn btn-success btn-small" type="submit">Aprobar</button>
                </form>
                <form method="post" data-confirm="¿Rechazar este registro?">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="usuario_id" value="<?= (int) $v['id'] ?>">
                    <input type="hidden" name="accion" value="rechazar">
                    <button class="btn btn-danger btn-small" type="submit">Rechazar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<form action="/logout.php" method="post" class="logout-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button type="submit" class="btn btn-outline">Cerrar sesión</button></form>
