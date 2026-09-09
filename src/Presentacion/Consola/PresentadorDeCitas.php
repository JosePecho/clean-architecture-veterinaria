<?php

declare(strict_types=1);

namespace Vetcare\Presentacion\Consola;

use Vetcare\Aplicacion\DTO\CitaDTO;

/** Formatea la salida para la consola. Solo conoce DTOs, nunca el Dominio. */
final class PresentadorDeCitas
{
    public function titulo(string $texto): void
    {
        echo PHP_EOL . "== {$texto} ==" . PHP_EOL;
    }

    public function cita(CitaDTO $cita): void
    {
        printf("      -> [%s] %s con %s%s", $cita->id, $cita->mascota, $cita->veterinario, PHP_EOL);
        printf(
            "         %s | estado: %s | motivo: %s | costo: %s%s",
            $cita->franja,
            $cita->estado,
            $cita->motivo,
            $cita->costo,
            PHP_EOL,
        );

        if ($cita->diagnostico !== null) {
            printf("         diagnostico: %s%s", $cita->diagnostico, PHP_EOL);
        }
    }

    public function rechazo(string $mensaje): void
    {
        printf("      xx RECHAZADO: %s%s", $mensaje, PHP_EOL);
    }
}
