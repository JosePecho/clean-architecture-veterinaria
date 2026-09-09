<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

/**
 * Value Object. No tiene identidad: "perro" siempre vale lo mismo que "perro".
 * Analogia del documento: como el tiempo de coccion, importa el valor, no la identidad.
 */
enum Especie: string
{
    case PERRO = 'perro';
    case GATO = 'gato';
    case AVE = 'ave';
    case REPTIL = 'reptil';
    case ROEDOR = 'roedor';

    /** Las especies exoticas requieren manejo especializado (regla usada por la tarifa). */
    public function esExotica(): bool
    {
        return in_array($this, [self::AVE, self::REPTIL], true);
    }
}
