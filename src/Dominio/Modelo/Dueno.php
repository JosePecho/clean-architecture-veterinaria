<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo;

use Vetcare\Dominio\ValueObject\Email;
use Vetcare\Dominio\ValueObject\Telefono;

/**
 * Entity con identidad propia (id). Sus datos de contacto son Value Objects
 * que se validan a si mismos al construirse.
 */
final class Dueno
{
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        public readonly Telefono $telefono,
        public readonly Email $email,
    ) {
    }
}
