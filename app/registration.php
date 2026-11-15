<?php
declare(strict_types=1);

/** El inicio es inclusivo y el cierre exclusivo, siempre en hora de Guatemala. */
function registration_window(?DateTimeImmutable $now = null): array
{
    $timezone = new DateTimeZone('America/Guatemala');
    $dates = [];
    foreach (['REGISTRO_APERTURA', 'REGISTRO_CIERRE'] as $key) {
        $value = trim(env_value($key));
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $value)) {
            $dates = [];
            break;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $timezone);
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            $dates = [];
            break;
        }
        $dates[] = $date;
    }

    if (count($dates) !== 2 || $dates[0] >= $dates[1]) {
        return [
            'status' => 'unconfigured',
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
            'message' => 'El registro no está disponible. El horario de inscripción está pendiente de configuración.',
        ];
    }

    [$opensAt, $closesAt] = $dates;
    $now ??= new DateTimeImmutable('now', $timezone);
    if ($now < $opensAt) {
        $status = 'pending';
        $message = 'El registro todavía no ha iniciado.';
    } elseif ($now >= $closesAt) {
        $status = 'closed';
        $message = 'El período de registro ha finalizado. Ya no se aceptan solicitudes.';
    } else {
        $status = 'open';
        $message = 'El registro está abierto.';
    }

    return [
        'status' => $status,
        'is_open' => $status === 'open',
        'opens_at' => $opensAt,
        'closes_at' => $closesAt,
        'message' => $message,
    ];
}
