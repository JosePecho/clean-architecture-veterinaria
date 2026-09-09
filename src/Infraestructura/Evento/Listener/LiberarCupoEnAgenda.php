<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento\Listener;

use Closure;
use Vetcare\Dominio\Evento\CitaCanceladaEvent;

/** Reacciona a "cita.cancelada": libera el cupo para que otros lo puedan tomar. */
final class LiberarCupoEnAgenda
{
    /** @param Closure(string): void $emitir */
    public function __construct(private readonly Closure $emitir)
    {
    }

    public function __invoke(CitaCanceladaEvent $evento): void
    {
        ($this->emitir)(sprintf(
            '[agenda] Cupo de la cita %s liberado. Motivo: %s.',
            $evento->citaId,
            $evento->motivo,
        ));
    }
}
