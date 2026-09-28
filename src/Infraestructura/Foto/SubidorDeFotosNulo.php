<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Foto;

use Vetcare\Dominio\Contrato\SubidorDeFotos;

final class SubidorDeFotosNulo implements SubidorDeFotos
{
    public function subir(?array $archivo): ?string
    {
        return null;
    }
}