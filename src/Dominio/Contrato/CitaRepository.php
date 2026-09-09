<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use DateTimeImmutable;
use Vetcare\Dominio\Modelo\Cita;

/** Repository Interface para la Entity raiz Cita. */
interface CitaRepository
{
    public function guardar(Cita $cita): void;

    public function buscarPorId(string $id): ?Cita;

    /** @return Cita[] */
    public function listarTodas(): array;

    /**
     * Citas de un veterinario en una fecha dada (para detectar solapamientos).
     *
     * @return Cita[]
     */
    public function listarDeVeterinarioEnFecha(string $veterinarioId, DateTimeImmutable $fecha): array;

    /** Genera el proximo identificador de cita. */
    public function siguienteId(): string;
}
