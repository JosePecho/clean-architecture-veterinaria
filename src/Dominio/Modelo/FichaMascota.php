<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo;

use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;

final class FichaMascota
{
    public function __construct(
        public readonly string $mascotaId,
        public readonly string $sexo,
        public readonly float $pesoKg,
        public readonly bool $esterilizado,
        public readonly string $colorPelaje,
        public readonly ?string $microchip = null,
        public readonly ?string $alergias = null,
        public readonly ?string $fotoUrl = null,
    ) {
        if (trim($this->mascotaId) === '') {
            throw new ReglaDeNegocioViolada('El ID de mascota no puede estar vacio.');
        }

        if (!in_array($this->sexo, ['macho', 'hembra'], true)) {
            throw new ReglaDeNegocioViolada('El sexo debe ser "macho" o "hembra".');
        }

        if ($this->pesoKg <= 0 || $this->pesoKg > 200) {
            throw new ReglaDeNegocioViolada('El peso debe estar entre 0 y 200 kg.');
        }

        if (trim($this->colorPelaje) === '') {
            throw new ReglaDeNegocioViolada('El color de pelaje es obligatorio.');
        }
    }

    public function mascotaId(): string
    {
        return $this->mascotaId;
    }

    public function sexo(): string
    {
        return $this->sexo;
    }

    public function pesoKg(): float
    {
        return $this->pesoKg;
    }

    public function esterilizado(): bool
    {
        return $this->esterilizado;
    }

    public function colorPelaje(): string
    {
        return $this->colorPelaje;
    }

    public function microchip(): ?string
    {
        return $this->microchip;
    }

    public function alergias(): ?string
    {
        return $this->alergias;
    }

    public function fotoUrl(): ?string
    {
        return $this->fotoUrl;
    }
}
