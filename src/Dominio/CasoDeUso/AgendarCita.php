<?php

declare(strict_types=1);

namespace Vetcare\Dominio\CasoDeUso;

use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Contrato\MascotaRepository;
use Vetcare\Dominio\Contrato\Reloj;
use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;
use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\Servicio\AgendaDelVeterinario;
use Vetcare\Dominio\Servicio\CalculadoraDeTarifa;
use Vetcare\Dominio\ValueObject\FranjaHoraria;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Use Case: accion completa de negocio de principio a fin.
 * Orquesta varias Entities (Mascota, Veterinario, Cita) y Domain Services
 * (agenda, tarifa) sin que ninguna de ellas conozca a las demas.
 */
final class AgendarCita
{
    public function __construct(
        private readonly MascotaRepository $mascotas,
        private readonly VeterinarioRepository $veterinarios,
        private readonly CitaRepository $citas,
        private readonly AgendaDelVeterinario $agenda,
        private readonly CalculadoraDeTarifa $tarifas,
        private readonly DespachadorDeEventos $eventos,
        private readonly Reloj $reloj,
    ) {
    }

    public function ejecutar(
        string $mascotaId,
        string $veterinarioId,
        string $inicioIso,
        string $motivoValor,
    ): string {
        $mascota = $this->mascotas->buscarPorId($mascotaId)
            ?? throw new RecursoNoEncontrado("La mascota \"{$mascotaId}\" no existe.");

        $veterinario = $this->veterinarios->buscarPorId($veterinarioId)
            ?? throw new RecursoNoEncontrado("El veterinario \"{$veterinarioId}\" no existe.");

        $motivo = MotivoDeConsulta::tryFrom($motivoValor)
            ?? throw new ReglaDeNegocioViolada("Motivo de consulta desconocido: \"{$motivoValor}\".");

        // El Value Object se valida a si mismo (fin > inicio, duracion minima).
        $franja = FranjaHoraria::desde($inicioIso, $motivo->duracionEstimadaEnMinutos());
        $ahora = $this->reloj->ahora();

        // Regla que cruza varias Entities -> Domain Service.
        $citasDelDia = $this->citas->listarDeVeterinarioEnFecha($veterinarioId, $franja->inicio);
        if (!$this->agenda->estaDisponible($veterinario, $franja, $citasDelDia)) {
            throw new ReglaDeNegocioViolada(
                'El veterinario no esta disponible en esa franja (solapamiento u horario).'
            );
        }

        $costo = $this->tarifas->calcular($motivo, $mascota, $ahora);

        // La Entity aplica sus propias Business Rules al construirse.
        $cita = Cita::agendar(
            $this->citas->siguienteId(),
            $mascota,
            $veterinario,
            $franja,
            $motivo,
            $costo,
            $ahora,
        );

        $this->citas->guardar($cita);
        $this->eventos->despachar($cita->extraerEventos());

        return $cita->id;
    }
}
