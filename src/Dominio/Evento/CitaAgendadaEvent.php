<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Evento;

use DateTimeImmutable;

final class CitaAgendadaEvent implements EventoDeDominio
{
    public function __construct(
        public readonly string $citaId,
        public readonly string $mascotaId,
        public readonly string $veterinarioId,
        public readonly string $franja,
        private readonly DateTimeImmutable $ocurridoEn,
    ) {
    }

    public function nombre(): string
    {
        return 'cita.agendada';
    }

    public function ocurridoEn(): DateTimeImmutable
    {
        return $this->ocurridoEn;
    }
}
