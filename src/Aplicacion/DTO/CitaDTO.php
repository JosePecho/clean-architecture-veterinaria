<?php

declare(strict_types=1);

namespace Vetcare\Aplicacion\DTO;

use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\Modelo\Mascota;
use Vetcare\Dominio\Modelo\Veterinario;

/**
 * DTO de salida. Aplana las Entities y Value Objects del Dominio a texto plano
 * para que la Presentacion no dependa del modelo de dominio.
 */
final class CitaDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $mascota,
        public readonly string $veterinario,
        public readonly string $franja,
        public readonly string $estado,
        public readonly string $motivo,
        public readonly string $costo,
        public readonly ?string $diagnostico,
    ) {
    }

    public static function desde(Cita $cita, ?Mascota $mascota, ?Veterinario $veterinario): self
    {
        return new self(
            $cita->id,
            $mascota?->nombre ?? $cita->mascotaId,
            $veterinario?->nombre ?? $cita->veterinarioId,
            (string) $cita->franja(),
            $cita->estado()->value,
            $cita->motivo->etiqueta(),
            (string) $cita->costo,
            $cita->diagnostico() !== null ? (string) $cita->diagnostico() : null,
        );
    }
}
