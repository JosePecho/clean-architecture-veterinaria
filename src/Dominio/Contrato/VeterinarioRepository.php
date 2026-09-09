<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use Vetcare\Dominio\Modelo\Veterinario;

/** Repository Interface para la Entity Veterinario. */
interface VeterinarioRepository
{
    public function buscarPorId(string $id): ?Veterinario;

    /** @return Veterinario[] */
    public function listarTodos(): array;

    public function guardar(Veterinario $veterinario): void;
}
