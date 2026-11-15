<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
$registrationWindow = registration_window();
$votingWindow = voting_window();
page_start('Sistema de Votación');
?>
<section class="hero">
    <h2>ELECCIONES COMUNALES DE CASERÍO LA LIBERTAD</h2>
    <p>Período 2027</p>
</section>
<?php show_registration_window($registrationWindow); ?>
<?php show_voting_window($votingWindow); ?>
<nav class="actions" aria-label="Menú principal">
    <?php if ($registrationWindow['is_open']): ?>
        <a class="btn btn-primary" href="/registro.php">Registrarse para votar</a>
    <?php endif; ?>
    <a class="btn btn-secondary" href="/login_votacion.php">Acceder a votación</a>
</nav>

