<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento\Listener;

use Closure;
use Vetcare\Dominio\Evento\CitaCompletadaEvent;

/** Reacciona a "cita.completada": anota el diagnostico en la historia clinica. */
final class RegistrarEnHistoriaClinica
{
    /** @param Closure(string): void $emitir */
    public function __construct(private readonly Closure $emitir)
    {
    }

    public function __invoke(CitaCompletadaEvent $evento): void
    {
        ($this->emitir)(sprintf(
            '[historia clinica] Mascota %s, cita %s: "%s".',
            $evento->mascotaId,
            $evento->citaId,
            $evento->diagnostico,
        ));
    }
}
