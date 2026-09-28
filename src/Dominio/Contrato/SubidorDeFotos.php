<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

interface SubidorDeFotos
{
    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $archivo
     * @return string|null URL relativa o null si no se subio nada
     */
    public function subir(?array $archivo): ?string;
}