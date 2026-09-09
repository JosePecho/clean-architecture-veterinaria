<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Evento;

use DateTimeImmutable;

/**
 * Domain Event. El Dominio "grita: pedido listo, mesa cinco" (analogia del documento)
 * sin saber quien escucha. Las capas externas deciden que hacer con el anuncio.
 */
interface EventoDeDominio
{
    /** Nombre estable con el que las capas externas se suscriben. */
    public function nombre(): string;

    public function ocurridoEn(): DateTimeImmutable;
}
