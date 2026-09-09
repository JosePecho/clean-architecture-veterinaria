<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Servicio;

use DateTimeImmutable;
use Vetcare\Dominio\Modelo\Mascota;
use Vetcare\Dominio\ValueObject\Dinero;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Domain Service. El precio de una cita depende de la combinacion de varias cosas
 * (el motivo + la especie + la edad de la mascota), no de una sola Entity.
 * Devuelve un Value Object Dinero.
 */
final class CalculadoraDeTarifa
{
    /** Tarifa base en soles por motivo de consulta. */
    private const BASE_SOLES = [
        'consulta_general' => 60.0,
        'control' => 40.0,
        'vacunacion' => 55.0,
        'emergencia' => 120.0,
        'cirugia_menor' => 250.0,
    ];

    private const RECARGO_ESPECIE_EXOTICA = 30.0;
    private const RECARGO_PROTOCOLO_CACHORRO = 15.0;

    public function calcular(MotivoDeConsulta $motivo, Mascota $mascota, DateTimeImmutable $hoy): Dinero
    {
        $tarifa = Dinero::soles(self::BASE_SOLES[$motivo->value]);

        if ($mascota->especie->esExotica()) {
            $tarifa = $tarifa->aplicarRecargo(self::RECARGO_ESPECIE_EXOTICA);
        }

        if ($mascota->esCachorro($hoy) && $motivo === MotivoDeConsulta::CONSULTA_GENERAL) {
            $tarifa = $tarifa->aplicarRecargo(self::RECARGO_PROTOCOLO_CACHORRO);
        }

        return $tarifa;
    }
}
