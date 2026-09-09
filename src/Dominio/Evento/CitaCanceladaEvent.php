<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Evento;

use DateTimeImmutable;

final class CitaCanceladaEvent implements EventoDeDominio
{
    public function __construct(
        public readonly string $citaId,
        public readonly string $motivo,
        private readonly DateTimeImmutable $ocurridoEn,
    ) {
    }

    public function nombre(): string
    {
        return 'cita.cancelada';
    }

    public function ocurridoEn(): DateTimeImmutable
    {
        return $this->ocurridoEn;
    }
}
