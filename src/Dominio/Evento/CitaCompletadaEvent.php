<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Evento;

use DateTimeImmutable;

final class CitaCompletadaEvent implements EventoDeDominio
{
    public function __construct(
        public readonly string $citaId,
        public readonly string $mascotaId,
        public readonly string $diagnostico,
        private readonly DateTimeImmutable $ocurridoEn,
    ) {
    }

    public function nombre(): string
    {
        return 'cita.completada';
    }

    public function ocurridoEn(): DateTimeImmutable
    {
        return $this->ocurridoEn;
    }
}
