<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\EnMemoria;

use Vetcare\Dominio\Contrato\FichaMascotaRepositorio;
use Vetcare\Dominio\Modelo\FichaMascota;

final class FichaMascotaEnMemoriaRepository implements FichaMascotaRepositorio
{
    /**
     * @var array<string, FichaMascota>
     */
    private array $porMascotaId = [];

    public function guardar(FichaMascota $ficha): void
    {
        $this->porMascotaId[$ficha->mascotaId] = $ficha;
    }

    public function buscar(string $mascotaId): ?FichaMascota
    {
        return $this->porMascotaId[$mascotaId] ?? null;
    }

    /**
     * @return array<string, FichaMascota>
     */
    public function todos(): array
    {
        return $this->porMascotaId;
    }
}
