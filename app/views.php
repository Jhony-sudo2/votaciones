<?php
declare(strict_types=1);

function page_start(string $title, string $subtitle = 'Elecciones Comunales 2027 · Caserío La Libertad'): void
{
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= h($title) ?> · Caserío La Libertad</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<main class="shell">
    <header class="masthead">
        <a href="/index.php" aria-label="Ir al inicio"><img class="logo" src="/assets/logo.svg" alt="Emblema de votación"></a>
        <div>
            <h1><?= h($title) ?></h1>
            <p class="subtitle"><?= h($subtitle) ?></p>
        </div>
    </header>
<?php
}

function show_registration_window(array $window): void
{
    $labels = ['pending' => 'Pendiente', 'open' => 'Abierto', 'closed' => 'Cerrado', 'unconfigured' => 'No disponible'];
    ?>
    <section class="user-info" aria-label="Horario de registro">
        <h2>Registro de votantes: <?= h($labels[$window['status']]) ?></h2>
        <p><?= h($window['message']) ?></p>
        <?php if ($window['opens_at'] !== null && $window['closes_at'] !== null): ?>
            <p>Apertura: <time datetime="<?= h($window['opens_at']->format(DateTimeInterface::ATOM)) ?>"><?= h($window['opens_at']->format('d/m/Y H:i:s')) ?></time><br>
            Cierre: <time datetime="<?= h($window['closes_at']->format(DateTimeInterface::ATOM)) ?>"><?= h($window['closes_at']->format('d/m/Y H:i:s')) ?></time></p>
            <p class="hint">Hora de Guatemala. Se aceptan solicitudes desde la apertura y hasta antes del cierre.</p>
        <?php endif; ?>
    </section>
    <?php
}

