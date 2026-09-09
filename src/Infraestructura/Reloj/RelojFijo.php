<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Reloj;

use DateTimeImmutable;
use Vetcare\Dominio\Contrato\Reloj;

/**
 * Reloj congelado en un instante. Lo usa el demo (y lo usarian los tests)
 * para que las reglas temporales den siempre el mismo resultado.
 */
final class RelojFijo implements Reloj
{
    public function __construct(private readonly DateTimeImmutable $momento)
    {
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->momento;
    }
}
