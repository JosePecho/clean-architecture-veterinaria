<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;

/** Use Case: el dueno confirma que asistira. PROGRAMADA -> CONFIRMADA. */
final class ConfirmarCita
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly DespachadorDeEventos $eventos,
    ) {
    }

    public function ejecutar(string $citaId): void
    {
        $cita = $this->citas->buscarPorId($citaId)
            ?? throw new RecursoNoEncontrado("La cita \"{$citaId}\" no existe.");

        $cita->confirmar();

        $this->citas->guardar($cita);
        $this->eventos->despachar($cita->extraerEventos());
    }
}
