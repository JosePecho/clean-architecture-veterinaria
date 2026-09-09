<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

use InvalidArgumentException;

/** Value Object: numero movil peruano (9 digitos que empiezan en 9). */
final class Telefono
{
    public readonly string $valor;

    public function __construct(string $valor)
    {
        $limpio = preg_replace('/\D/', '', $valor) ?? '';

        if (!preg_match('/^9\d{8}$/', $limpio)) {
            throw new InvalidArgumentException("Telefono movil peruano invalido: {$valor}");
        }

        $this->valor = $limpio;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
