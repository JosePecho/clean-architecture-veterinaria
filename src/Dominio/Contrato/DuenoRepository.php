<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use Vetcare\Dominio\Modelo\Dueno;

/** Repository Interface para la Entity Dueno. */
interface DuenoRepository
{
    public function buscarPorId(string $id): ?Dueno;

    public function guardar(Dueno $dueno): void;
}
