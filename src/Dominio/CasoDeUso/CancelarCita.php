<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Contrato\Reloj;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;

/** Use Case: cancelar una cita (solo si su estado lo permite). */
final class CancelarCita
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly DespachadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {
    }

    public function ejecutar(string $citaId, string $motivo): void
    {
        $cita = $this->citas->buscarPorId($citaId)
            ?? throw new RecursoNoEncontrado("La cita \"{$citaId}\" no existe.");

        $cita->cancelar($motivo, $this->reloj->ahora());

        $this->citas->guardar($cita);
        $this->eventos->despachar($cita->extraerEventos());
    }
}
