<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use DateTimeImmutable;

/**
 * Puerto. El Dominio necesita saber "que hora es" para aplicar reglas temporales,
 * pero no debe llamar a new DateTimeImmutable('now') directamente: eso lo haria
 * imposible de testear de forma determinista.
 */
interface Reloj
{
    public function ahora(): DateTimeImmutable;
}
