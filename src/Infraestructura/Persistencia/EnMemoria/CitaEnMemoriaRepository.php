<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\EnMemoria;

use DateTimeImmutable;
use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Modelo\Cita;

final class CitaEnMemoriaRepository implements CitaRepository
{
    /** @var array<string, Cita> */
    private array $porId = [];

    public function guardar(Cita $cita): void
    {
        $this->porId[$cita->id] = $cita;
    }

    public function buscarPorId(string $id): ?Cita
    {
        return $this->porId[$id] ?? null;
    }

    public function listarTodas(): array
    {
        return array_values($this->porId);
    }

    public function listarDeVeterinarioEnFecha(string $veterinarioId, DateTimeImmutable $fecha): array
    {
        $dia = $fecha->format('Y-m-d');

        return array_values(array_filter(
            $this->porId,
            static fn (Cita $cita): bool => $cita->veterinarioId === $veterinarioId
                && $cita->franja()->inicio->format('Y-m-d') === $dia,
        ));
    }

    public function siguienteId(): string
    {
        // Se calcula al vuelo: un intento de agendar que termine rechazado
        // no "consume" un numero de cita.
        return sprintf('CITA-%03d', count($this->porId) + 1);
    }
}
