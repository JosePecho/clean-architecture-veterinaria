<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use Vetcare\Dominio\Modelo\FichaMascota;

interface FichaMascotaRepositorio
{
    public function guardar(FichaMascota $ficha): void;

    public function buscar(string $mascotaId): ?FichaMascota;

    /**
     * @return array<string, FichaMascota>
     */
    public function todos(): array;
}
