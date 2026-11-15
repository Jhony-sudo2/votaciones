<?php
declare(strict_types=1);

/** El ID de registro evita contadores en memoria y colisiones entre aprobaciones. */
function voter_username(int $registrationId): string
{
    if ($registrationId < 1) {
        throw new InvalidArgumentException('El número de registro debe ser positivo.');
    }
    return 'votante_' . $registrationId;
}

function voter_password(): string
{
    // 32 caracteres posibles, 16 elecciones independientes: 80 bits de aleatoriedad.
    // Solo mayúsculas y sin I/O/0/1 para facilitar la transcripción.
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $password = '';
    for ($i = 0; $i < 16; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $password;
}
