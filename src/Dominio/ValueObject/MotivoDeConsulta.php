<?php

declare(strict_types=1);

namespace Vetcare\Dominio\ValueObject;

/**
 * Value Object. Cada motivo lleva asociada su duracion estimada:
 * el conocimiento del negocio ("una cirugia menor toma 90 minutos") vive aqui,
 * no en un formulario ni en la base de datos.
 */
enum MotivoDeConsulta: string
{
    case CONSULTA_GENERAL = 'consulta_general';
    case CONTROL = 'control';
    case VACUNACION = 'vacunacion';
    case EMERGENCIA = 'emergencia';
    case CIRUGIA_MENOR = 'cirugia_menor';

    public function duracionEstimadaEnMinutos(): int
    {
        return match ($this) {
            self::CONTROL => 20,
            self::CONSULTA_GENERAL, self::VACUNACION => 30,
            self::EMERGENCIA => 45,
            self::CIRUGIA_MENOR => 90,
        };
    }

    public function etiqueta(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
