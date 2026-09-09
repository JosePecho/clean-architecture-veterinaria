<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\EnMemoria;

use Vetcare\Dominio\Contrato\MascotaRepository;
use Vetcare\Dominio\Modelo\Mascota;

/**
 * Implementacion de Infraestructura. Aqui SI se puede hablar de "como" se guarda
 * (en este caso, un array en memoria). Una version con PDO/MySQL viviria al lado,
 * cumpliendo la misma interfaz del Dominio.
 */
final class MascotaEnMemoriaRepository implements MascotaRepository
{
    /** @var array<string, Mascota> */
    private array $porId = [];

    public function buscarPorId(string $id): ?Mascota
    {
        return $this->porId[$id] ?? null;
    }

    public function listarTodas(): array
    {
        return array_values($this->porId);
    }

    public function guardar(Mascota $mascota): void
    {
        $this->porId[$mascota->id] = $mascota;
    }
}
