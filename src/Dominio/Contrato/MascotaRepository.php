<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use Vetcare\Dominio\Modelo\Mascota;

/**
 * Repository Interface. El Dominio dice QUE necesita ("dame la mascota con este id"),
 * nunca COMO se consigue. La implementacion real vive en Infraestructura.
 */
interface MascotaRepository
{
    public function buscarPorId(string $id): ?Mascota;

    /** @return Mascota[] */
    public function listarTodas(): array;

    public function guardar(Mascota $mascota): void;
}
