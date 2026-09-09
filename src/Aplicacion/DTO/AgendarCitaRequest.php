<?php

declare(strict_types=1);

namespace Vetcare\Aplicacion\DTO;

/**
 * DTO de entrada. La capa de Presentacion arma este objeto con datos primitivos
 * (lo que llega de un formulario, una API o la consola) y se lo pasa a la Aplicacion.
 */
final class AgendarCitaRequest
{
    public function __construct(
        public readonly string $mascotaId,
        public readonly string $veterinarioId,
        public readonly string $inicio,
        public readonly string $motivo,
    ) {
    }
}
