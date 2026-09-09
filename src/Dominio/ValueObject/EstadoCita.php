<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

/**
 * Value Object que ademas encapsula una Business Rule central:
 * "el estado de una cita solo avanza, nunca retrocede"
 * (equivalente a la regla del Pedido en el documento).
 */
enum EstadoCita: string
{
    case PROGRAMADA = 'programada';
    case CONFIRMADA = 'confirmada';
    case EN_ATENCION = 'en_atencion';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';

    /** @return EstadoCita[] Estados a los que se puede pasar desde el actual. */
    public function siguientesPosibles(): array
    {
        return match ($this) {
            self::PROGRAMADA => [self::CONFIRMADA, self::CANCELADA],
            self::CONFIRMADA => [self::EN_ATENCION, self::CANCELADA],
            self::EN_ATENCION => [self::COMPLETADA],
            self::COMPLETADA, self::CANCELADA => [],
        };
    }

    public function puedeAvanzarA(EstadoCita $destino): bool
    {
        return in_array($destino, $this->siguientesPosibles(), true);
    }

    /** Un estado final ya no admite ninguna transicion. */
    public function esFinal(): bool
    {
        return $this->siguientesPosibles() === [];
    }
}
