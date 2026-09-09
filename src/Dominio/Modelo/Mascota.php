<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo;

use DateTimeImmutable;
use Vetcare\Dominio\ValueObject\Especie;

/**
 * Entity. Igual que el "Plato" del documento: existe en la realidad del negocio
 * antes que cualquier sistema. La responsabilidad vive donde vive el dato:
 * la propia Mascota sabe calcular su edad y si es cachorro.
 */
final class Mascota
{
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        public readonly Especie $especie,
        public readonly DateTimeImmutable $fechaNacimiento,
        public readonly string $duenoId,
    ) {
    }

    public function edadEnMeses(DateTimeImmutable $hoy): int
    {
        $diferencia = $this->fechaNacimiento->diff($hoy);

        return $diferencia->y * 12 + $diferencia->m;
    }

    public function esCachorro(DateTimeImmutable $hoy): bool
    {
        return $this->edadEnMeses($hoy) < 12;
    }
}
