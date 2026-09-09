<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Contrato;

use Vetcare\Dominio\Evento\EventoDeDominio;

/**
 * Puerto de salida. El Use Case entrega aqui los Domain Events que produjo la Entity.
 * Quien realmente los reparte (memoria, cola de mensajes, bus) vive en Infraestructura.
 */
interface DespachadorDeEventos
{
    /** @param EventoDeDominio[] $eventos */
    public function despachar(array $eventos): void;
}
