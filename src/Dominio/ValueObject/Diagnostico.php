<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

use InvalidArgumentException;

/** Value Object: el texto clinico con el que se cierra una cita. */
final class Diagnostico
{
    public readonly string $texto;

    public function __construct(string $texto)
    {
        $limpio = trim($texto);

        if (mb_strlen($limpio) < 5) {
            throw new InvalidArgumentException('El diagnostico es demasiado breve.');
        }

        $this->texto = $limpio;
    }

    public function __toString(): string
    {
        return $this->texto;
    }
}
