<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

use InvalidArgumentException;

/**
 * Value Object. Es el "pequeno guardian de la calidad del dato" del documento:
 * nunca se puede construir con un monto negativo.
 * Se guarda en centimos (enteros) para evitar errores de coma flotante.
 */
final class Dinero
{
    private function __construct(
        public readonly int $centimos,
        public readonly string $moneda,
    ) {
    }

    public static function soles(float $monto): self
    {
        return self::deCentimos((int) round($monto * 100), 'PEN');
    }

    public static function deCentimos(int $centimos, string $moneda = 'PEN'): self
    {
        if ($centimos < 0) {
            throw new InvalidArgumentException('El dinero no puede ser negativo.');
        }

        return new self($centimos, $moneda);
    }

    public static function cero(string $moneda = 'PEN'): self
    {
        return new self(0, $moneda);
    }

    public function sumar(self $otro): self
    {
        $this->exigirMismaMoneda($otro);

        return new self($this->centimos + $otro->centimos, $this->moneda);
    }

    public function aplicarRecargo(float $porcentaje): self
    {
        if ($porcentaje < 0) {
            throw new InvalidArgumentException('El recargo no puede ser negativo.');
        }

        return new self((int) round($this->centimos * (1 + $porcentaje / 100)), $this->moneda);
    }

    public function esCero(): bool
    {
        return $this->centimos === 0;
    }

    public function equals(self $otro): bool
    {
        return $this->centimos === $otro->centimos && $this->moneda === $otro->moneda;
    }

    public function __toString(): string
    {
        return sprintf('%s %.2f', $this->moneda, $this->centimos / 100);
    }

    private function exigirMismaMoneda(self $otro): void
    {
        if ($this->moneda !== $otro->moneda) {
            throw new InvalidArgumentException('No se puede operar dinero de distinta moneda.');
        }
    }
}
