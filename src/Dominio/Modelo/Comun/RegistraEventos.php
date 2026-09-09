<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo\Comun;

use Vetcare\Dominio\Evento\EventoDeDominio;

/**
 * Trait para las Entities que emiten Domain Events.
 * La Entity solo los acumula; el Use Case los extrae despues de guardar
 * y se los pasa al despachador. Asi el Dominio nunca conoce al que escucha.
 */
trait RegistraEventos
{
    /** @var EventoDeDominio[] */
    private array $eventosPendientes = [];

    protected function registrarEvento(EventoDeDominio $evento): void
    {
        $this->eventosPendientes[] = $evento;
    }

    /** @return EventoDeDominio[] Devuelve los eventos y vacia la cola. */
    public function extraerEventos(): array
    {
        $eventos = $this->eventosPendientes;
        $this->eventosPendientes = [];

        return $eventos;
    }
}
