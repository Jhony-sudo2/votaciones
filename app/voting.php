<?php
declare(strict_types=1);

/** La votación se habilita desde la apertura y no tiene cierre automático. */
function voting_window(?DateTimeImmutable $now = null): array
{
    $timezone = new DateTimeZone('America/Guatemala');
    $value = trim(env_value('VOTACION_APERTURA'));
    $opensAt = false;
    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $value)) {
        $opensAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $timezone);
    }
    if ($opensAt === false || $opensAt->format('Y-m-d H:i:s') !== $value) {
        return [
            'status' => 'unconfigured',
            'is_open' => false,
            'opens_at' => null,
            'message' => 'La votación no está disponible. El horario de apertura está pendiente de configuración.',
        ];
    }

    $now ??= new DateTimeImmutable('now', $timezone);
    $isOpen = $now >= $opensAt;
    return [
        'status' => $isOpen ? 'open' : 'pending',
        'is_open' => $isOpen,
        'opens_at' => $opensAt,
        'message' => $isOpen ? 'La votación está abierta.' : 'La votación todavía no ha iniciado.',
    ];
}
