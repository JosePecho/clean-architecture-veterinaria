<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

use InvalidArgumentException;

/** Value Object: correo electronico normalizado y validado. */
final class Email
{
    public readonly string $valor;

    public function __construct(string $valor)
    {
        $normalizado = strtolower(trim($valor));

        if (filter_var($normalizado, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Email invalido: {$valor}");
        }

        $this->valor = $normalizado;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
