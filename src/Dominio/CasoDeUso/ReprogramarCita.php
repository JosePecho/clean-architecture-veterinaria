<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Contrato\Reloj;
use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;
use Vetcare\Dominio\Servicio\AgendaDelVeterinario;
use Vetcare\Dominio\ValueObject\FranjaHoraria;

/**
 * Use Case: mover una cita a otra franja.
 * Reutiliza el Domain Service de agenda (excluyendo la propia cita) y deja
 * que la Entity aplique la regla de las 24 h de anticipacion.
 */
final class ReprogramarCita
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly VeterinarioRepository $veterinarios,
        private readonly AgendaDelVeterinario $agenda,
        private readonly DespachadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {
    }

    public function ejecutar(string $citaId, string $nuevoInicioIso): void
    {
        $cita = $this->citas->buscarPorId($citaId)
            ?? throw new RecursoNoEncontrado("La cita \"{$citaId}\" no existe.");

        $veterinario = $this->veterinarios->buscarPorId($cita->veterinarioId)
            ?? throw new RecursoNoEncontrado("El veterinario \"{$cita->veterinarioId}\" no existe.");

        $nueva = FranjaHoraria::desde($nuevoInicioIso, $cita->franja()->duracionEnMinutos());

        $otrasCitas = array_filter(
            $this->citas->listarDeVeterinarioEnFecha($cita->veterinarioId, $nueva->inicio),
            static fn ($c) => $c->id !== $cita->id,
        );

        if (!$this->agenda->estaDisponible($veterinario, $nueva, $otrasCitas)) {
            throw new ReglaDeNegocioViolada('El veterinario no esta disponible en la nueva franja.');
        }

        $cita->reprogramar($nueva, $this->reloj->ahora());

        $this->citas->guardar($cita);
        $this->eventos->despachar($cita->extraerEventos());
    }
}
