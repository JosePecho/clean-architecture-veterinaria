<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento\Listener;

use Closure;
use Vetcare\Dominio\Evento\CitaAgendadaEvent;

/**
 * Reacciona a "cita.agendada". El Dominio no sabe que esto existe.
 * Recibe un "sumidero" (Closure) para no acoplarse a como se muestra el mensaje
 * (consola en el demo, archivo de texto en la app web).
 */
final class EnviarRecordatorioAlDueno
{
    /** @param Closure(string): void $emitir */
    public function __construct(private readonly Closure $emitir)
    {
    }

    public function __invoke(CitaAgendadaEvent $evento): void
    {
        ($this->emitir)(sprintf(
            '[notificaciones] SMS al dueno: su cita %s quedo reservada para %s.',
            $evento->citaId,
            $evento->franja,
        ));
    }
}
