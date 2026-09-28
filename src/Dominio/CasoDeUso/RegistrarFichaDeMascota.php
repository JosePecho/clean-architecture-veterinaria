<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Modelo\FichaMascota;
use Vetcare\Dominio\Contrato\FichaMascotaRepositorio;
use Vetcare\Dominio\Contrato\SubidorDeFotos;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;

final class RegistrarFichaDeMascota
{
    public function __construct(
        private FichaMascotaRepositorio $fichas,
        private SubidorDeFotos $subidor,
    ) {
    }

    public function ejecutar(string $mascotaId, array $entrada, ?array $archivo): FichaMascota
    {
        $sexo = (string)($entrada['sexo'] ?? '');
        if (!in_array($sexo, ['macho', 'hembra'], true)) {
            throw new ReglaDeNegocioViolada('Selecciona un sexo valido.');
        }

        $peso = filter_var($entrada['peso_kg'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($peso === false || $peso <= 0 || $peso > 200) {
            throw new ReglaDeNegocioViolada('Peso invalido.');
        }

        $color = trim((string)($entrada['color_pelaje'] ?? ''));
        if ($color === '') {
            throw new ReglaDeNegocioViolada('El color de pelaje es obligatorio.');
        }

        $microchip = trim((string)($entrada['microchip'] ?? ''));
        $microchip = $microchip === '' ? null : $microchip;

        $alergias = trim((string)($entrada['alergias'] ?? ''));
        $alergias = $alergias === '' ? null : $alergias;

        $fotoUrl = $this->subidor->subir($archivo);

        $ficha = new FichaMascota(
            mascotaId: $mascotaId,
            sexo: $sexo,
            pesoKg: (float)$peso,
            esterilizado: (($entrada['esterilizado'] ?? 'no') === 'si'),
            colorPelaje: $color,
            microchip: $microchip,
            alergias: $alergias,
            fotoUrl: $fotoUrl,
        );

        $this->fichas->guardar($ficha);

        return $ficha;
    }
}