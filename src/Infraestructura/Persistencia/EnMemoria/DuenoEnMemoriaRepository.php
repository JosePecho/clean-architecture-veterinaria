<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\EnMemoria;

use Vetcare\Dominio\Contrato\DuenoRepository;
use Vetcare\Dominio\Modelo\Dueno;

final class DuenoEnMemoriaRepository implements DuenoRepository
{
    /** @var array<string, Dueno> */
    private array $porId = [];

    public function buscarPorId(string $id): ?Dueno
    {
        return $this->porId[$id] ?? null;
    }

    public function guardar(Dueno $dueno): void
    {
        $this->porId[$dueno->id] = $dueno;
    }
}
