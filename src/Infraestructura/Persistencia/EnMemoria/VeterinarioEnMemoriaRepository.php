<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\EnMemoria;

use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Modelo\Veterinario;

final class VeterinarioEnMemoriaRepository implements VeterinarioRepository
{
    /** @var array<string, Veterinario> */
    private array $porId = [];

    public function buscarPorId(string $id): ?Veterinario
    {
        return $this->porId[$id] ?? null;
    }

    public function listarTodos(): array
    {
        return array_values($this->porId);
    }

    public function guardar(Veterinario $veterinario): void
    {
        $this->porId[$veterinario->id] = $veterinario;
    }
}
