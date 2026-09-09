<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento\Listener;

use Closure;
use Vetcare\Dominio\Evento\CitaReprogramadaEvent;

/** Reacciona a "cita.reprogramada". */
final class AvisarReprogramacion
{
    /** @param Closure(string): void $emitir */
    public function __construct(private readonly Closure $emitir)
    {
    }

    public function __invoke(CitaReprogramadaEvent $evento): void
    {
        ($this->emitir)(sprintf(
            '[notificaciones] La cita %s se movio de [%s] a [%s].',
            $evento->citaId,
            $evento->franjaAnterior,
            $evento->franjaNueva,
        ));
    }
}
