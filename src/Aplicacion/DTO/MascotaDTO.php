<?php

declare(strict_types=1);

namespace Vetcare\Aplicacion\DTO;

final class MascotaDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        public readonly string $sexo,
        public readonly float $pesoKg,
        public readonly bool $esterilizado,
        public readonly string $colorPelaje,
        public readonly ?string $microchip,
        public readonly ?string $alergias,
        public readonly ?string $fotoUrl,
    ) {
    }
}
