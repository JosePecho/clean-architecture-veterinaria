<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Evento;

use DateTimeImmutable;

final class CitaReprogramadaEvent implements EventoDeDominio
{
    public function __construct(
        public readonly string $citaId,
        public readonly string $franjaAnterior,
        public readonly string $franjaNueva,
        private readonly DateTimeImmutable $ocurridoEn,
    ) {
    }

    public function nombre(): string
    {
        return 'cita.reprogramada';
    }

    public function ocurridoEn(): DateTimeImmutable
    {
        return $this->ocurridoEn;
    }
}
