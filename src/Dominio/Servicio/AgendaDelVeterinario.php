<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Servicio;

use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\Modelo\Veterinario;
use Vetcare\Dominio\ValueObject\EstadoCita;
use Vetcare\Dominio\ValueObject\FranjaHoraria;

/**
 * Domain Service. La regla "un veterinario no puede tener dos citas solapadas"
 * NO pertenece a una sola Cita: relaciona al Veterinario con TODAS sus citas del dia.
 * Es el equivalente al "ValidadorDeCombo" del documento.
 */
final class AgendaDelVeterinario
{
    /**
     * @param Cita[] $citasDelDia Citas ya registradas de ese veterinario en esa fecha.
     */
    public function estaDisponible(
        Veterinario $veterinario,
        FranjaHoraria $franja,
        array $citasDelDia,
    ): bool {
        if (!$veterinario->trabajaEn($franja)) {
            return false;
        }

        foreach ($citasDelDia as $cita) {
            if ($cita->estado() === EstadoCita::CANCELADA) {
                continue;
            }

            if ($cita->franja()->seSolapaCon($franja)) {
                return false;
            }
        }

        return true;
    }
}
