<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Contrato\Reloj;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\ValueObject\Diagnostico;

/**
 * Use Case: el veterinario atiende a la mascota y cierra la cita.
 * CONFIRMADA -> EN_ATENCION -> COMPLETADA, y emite el DomainEvent CitaCompletadaEvent.
 */
final class AtenderCita
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly DespachadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {
    }

    public function ejecutar(string $citaId, string $diagnosticoTexto): void
    {
        $cita = $this->citas->buscarPorId($citaId)
            ?? throw new RecursoNoEncontrado("La cita \"{$citaId}\" no existe.");

        $cita->iniciarAtencion();
        $cita->completar(new Diagnostico($diagnosticoTexto), $this->reloj->ahora());

        $this->citas->guardar($cita);
        $this->eventos->despachar($cita->extraerEventos());
    }
}
