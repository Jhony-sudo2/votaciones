<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_voter();
$id = (int) $_SESSION['usuario_id'];
$pdo = db();
$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $option = $_POST['opcion_voto'] ?? '';
    if (!is_string($option) || !in_array($option, ['opcion1', 'opcion2'], true)) {
        $error = 'Selecciona una de las dos planillas disponibles.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT estado, ha_votado FROM usuarios WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $voter = $stmt->fetch();
            if (!$voter || $voter['estado'] !== 'aprobado') {
                $pdo->rollBack();
                unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre']);
                set_flash('error', 'Tu usuario ya no tiene permiso para votar.');
                redirect('login_votacion.php');
            }
            if ((int) $voter['ha_votado'] !== 0) {
                $pdo->rollBack();
                $mensaje = 'Ya has ejercido tu derecho al voto.';
            } else {
                // El voto se guarda sin usuario_id ni fecha, y el estado del usuario
                // se actualiza dentro de la MISMA transacción para evitar duplicados.
                $stmt = $pdo->prepare('INSERT INTO votos (opcion_voto) VALUES (?)');
                $stmt->execute([$option]);
                $stmt = $pdo->prepare("UPDATE usuarios SET ha_votado = 1 WHERE id = ? AND ha_votado = 0 AND estado = 'aprobado'");
                $stmt->execute([$id]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('No se pudo actualizar el estado del votante.');
                }
                $pdo->commit();
                set_flash('success', '¡Tu voto fue registrado! No puedes volver a votar.');
                redirect('votacion.php');
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error en transacción de votación: ' . $exception->getMessage());
            $error = 'No se pudo registrar el voto. Vuelve a intentarlo.';
        }
    }
}

$stmt = $pdo->prepare('SELECT nombre_completo, estado, ha_votado FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$voter = $stmt->fetch();
if (!$voter || $voter['estado'] !== 'aprobado') {
    unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre']);
    set_flash('error', 'Tu cuenta no se encuentra habilitada.');
    redirect('login_votacion.php');
}
$alreadyVoted = (bool) $voter['ha_votado'];
page_start('Sistema de Votación');
?>
<div class="user-info">Bienvenido: <strong><?= h($voter['nombre_completo']) ?></strong></div>
<?php show_flash(); ?>
<?php if ($mensaje !== ''): ?>
    <div class="alert alert-success"><?= h($mensaje) ?></div><?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>

<?php if ($alreadyVoted): ?>
    <div class="alert alert-success">
        <h2>Voto registrado</h2>
        <p>Ya has ejercido tu derecho al voto. Gracias por participar.</p>
    </div>
<?php else: ?>
    <h2 class="section-title">Selecciona tu planilla</h2>
    <form method="post" id="votacionForm">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="choices" role="group" aria-label="Planillas disponibles">

            <!-- PLANILLA LA ANTORCHA -->
            <label class="choice">
                <input type="radio" name="opcion_voto" value="opcion1" required>

                <img src="/assets/antorcha.png" alt="Símbolo de la Planilla La Antorcha" class="choice-image">

                <span class="choice-content">
                    <strong>PLANILLA LA ANTORCHA</strong>
                    <span>Alcalde Auxiliar: Jaime Ovidio López Fuentes</span>
                    <span>Segundo Alcalde Auxiliar: Gerlin Eligio López Fuentes</span>
                </span>
            </label>

            <!-- PLANILLA LA PALOMA -->
            <label class="choice">
                <input type="radio" name="opcion_voto" value="opcion2" required>

                <img src="/assets/paloma.png" alt="Símbolo de la Planilla La Paloma" class="choice-image">

                <span class="choice-content">
                    <strong>PLANILLA LA PALOMA</strong>
                    <span>Alcalde Auxiliar: Donald Mefiboset Fuentes Orozco</span>
                    <span>Segundo Alcalde Auxiliar: Edilmar Bernaldino Monzón Miranda</span>
                </span>
            </label>

        </div>
        <button type="submit" id="btnVotar" class="btn btn-primary">Confirmar voto</button>
    </form>
<?php endif; ?>
<form action="/logout.php" method="post" class="logout-form">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <button type="submit" class="btn btn-outline">Cerrar sesión</button>
</form>
<?php page_end(); ?>