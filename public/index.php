<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
page_start('Sistema de Votación');
?>
<section class="hero">
    <h2>ELECCIONES COMUNALES DE CASERÍO LA LIBERTAD</h2>
    <p>Período 2027</p>
</section>
<nav class="actions" aria-label="Menú principal">
    <a class="btn btn-primary" href="/registro.php">Registrarse para votar</a>
    <a class="btn btn-secondary" href="/login_votacion.php">Acceder a votación</a>
    <a class="btn btn-outline" href="/login_operador.php">Acceso de operadores</a>
</nav>

