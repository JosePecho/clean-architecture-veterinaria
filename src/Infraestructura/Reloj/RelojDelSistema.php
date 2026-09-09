<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Reloj;

use DateTimeImmutable;
use Vetcare\Dominio\Contrato\Reloj;

/** Implementacion real: la hora del sistema operativo. */
final class RelojDelSistema implements Reloj
{
    public function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
