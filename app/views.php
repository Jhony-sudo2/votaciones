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

