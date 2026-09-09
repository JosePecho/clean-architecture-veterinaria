<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo;

use Vetcare\Dominio\ValueObject\Especie;
use Vetcare\Dominio\ValueObject\FranjaHoraria;

/**
 * Entity. Conoce las reglas de su propio ejercicio: que especies atiende
 * y en que horario trabaja. Nadie fuera de la Entity decide eso por ella.
 */
final class Veterinario
{
    /**
     * @param Especie[] $especiesQueAtiende Lista vacia = atiende todas las especies.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        private readonly array $especiesQueAtiende = [],
        private readonly int $horaInicioJornada = 8,
        private readonly int $horaFinJornada = 18,
    ) {
    }

    public function atiendeEspecie(Especie $especie): bool
    {
        return $this->especiesQueAtiende === []
            || in_array($especie, $this->especiesQueAtiende, true);
    }

    /** La franja completa debe caer dentro de la jornada laboral del veterinario. */
    public function trabajaEn(FranjaHoraria $franja): bool
    {
        $minutoInicio = (int) $franja->inicio->format('G') * 60 + (int) $franja->inicio->format('i');
        $minutoFin = (int) $franja->fin->format('G') * 60 + (int) $franja->fin->format('i');

        return $minutoInicio >= $this->horaInicioJornada * 60
            && $minutoFin <= $this->horaFinJornada * 60;
    }

    // --- Accesores usados solo por los repositorios para serializar la Entity ---

    /** @return Especie[] */
    public function especiesQueAtiende(): array
    {
        return $this->especiesQueAtiende;
    }

    public function horaInicioJornada(): int
    {
        return $this->horaInicioJornada;
    }

    public function horaFinJornada(): int
    {
        return $this->horaFinJornada;
    }
}
