<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Value Object. Un intervalo [inicio, fin) con sus validaciones internas:
 * el fin debe ser posterior al inicio y la duracion no puede ser ridicula.
 * No sabe "que hora es ahora": esa comparacion temporal es una Business Rule
 * que aplican la Entity Cita y los Use Cases.
 */
final class FranjaHoraria
{
    private const DURACION_MINIMA_MIN = 15;

    public readonly DateTimeImmutable $inicio;
    public readonly DateTimeImmutable $fin;

    public function __construct(DateTimeImmutable $inicio, DateTimeImmutable $fin)
    {
        if ($fin <= $inicio) {
            throw new InvalidArgumentException('La franja debe terminar despues de su inicio.');
        }

        $minutos = ($fin->getTimestamp() - $inicio->getTimestamp()) / 60;
        if ($minutos < self::DURACION_MINIMA_MIN) {
            throw new InvalidArgumentException(
                sprintf('La franja debe durar al menos %d minutos.', self::DURACION_MINIMA_MIN)
            );
        }

        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public static function desde(string $inicioIso, int $duracionMinutos): self
    {
        $inicio = new DateTimeImmutable($inicioIso);

        return new self($inicio, $inicio->modify("+{$duracionMinutos} minutes"));
    }

    public function duracionEnMinutos(): int
    {
        return (int) (($this->fin->getTimestamp() - $this->inicio->getTimestamp()) / 60);
    }

    /** Dos franjas se solapan si cada una empieza antes de que la otra termine. */
    public function seSolapaCon(self $otra): bool
    {
        return $this->inicio < $otra->fin && $otra->inicio < $this->fin;
    }

    public function empiezaAntesDe(DateTimeImmutable $momento): bool
    {
        return $this->inicio < $momento;
    }

    public function mismoDiaQue(DateTimeImmutable $momento): bool
    {
        return $this->inicio->format('Y-m-d') === $momento->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->inicio->format('Y-m-d H:i') . ' - ' . $this->fin->format('H:i');
    }
}
