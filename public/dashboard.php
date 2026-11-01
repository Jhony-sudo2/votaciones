<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_operator();
$stats = db()->query("SELECT COUNT(*) AS total, SUM(estado='pendiente') AS pendientes, SUM(estado='aprobado') AS aprobados, SUM(estado='rechazado') AS rechazados, SUM(ha_votado=1) AS votantes_que_votaron FROM usuarios")->fetch();
$results = ['opcion1' => 0, 'opcion2' => 0];
foreach (db()->query('SELECT opcion_voto, COUNT(*) AS cantidad FROM votos GROUP BY opcion_voto') as $row) {
    $results[$row['opcion_voto']] = (int) $row['cantidad'];
}
$totalVotes = array_sum($results);
page_start('Panel de resultados', 'Resumen administrativo');
?>
<nav class="topnav"><a class="btn btn-secondary" href="/operador.php">Validación de registros</a></nav>
<div class="stats">
    <div><span>Personas registradas</span><strong><?= (int) $stats['total'] ?></strong></div>
    <div><span>Aprobadas</span><strong><?= (int) $stats['aprobados'] ?></strong></div>
    <div><span>Pendientes</span><strong><?= (int) $stats['pendientes'] ?></strong></div>
    <div><span>Rechazadas</span><strong><?= (int) $stats['rechazados'] ?></strong></div>
    <div><span>Han votado</span><strong><?= (int) $stats['votantes_que_votaron'] ?></strong></div>
    <div><span>Votos contabilizados</span><strong><?= $totalVotes ?></strong></div>
</div>
<h2 class="section-title">Resultados por planilla</h2>
<div class="table-wrap"><table><thead><tr><th>Planilla</th><th>Votos</th><th>Porcentaje</th></tr></thead><tbody>
    <tr><td>Planilla La Antorcha</td><td><?= $results['opcion1'] ?></td><td><?= $totalVotes ? number_format(100 * $results['opcion1'] / $totalVotes, 1) : '0.0' ?>%</td></tr>
    <tr><td>Planilla La Paloma</td><td><?= $results['opcion2'] ?></td><td><?= $totalVotes ? number_format(100 * $results['opcion2'] / $totalVotes, 1) : '0.0' ?>%</td></tr>
    </tbody></table></div>
<form action="/logout.php" method="post" class="logout-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button type="submit" class="btn btn-outline">Cerrar sesión</button></form>
