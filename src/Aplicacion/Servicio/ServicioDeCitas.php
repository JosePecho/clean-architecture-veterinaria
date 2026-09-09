<?php

declare(strict_types=1);

namespace Vetcare\Aplicacion\Servicio;

use Vetcare\Aplicacion\DTO\AgendarCitaRequest;
use Vetcare\Aplicacion\DTO\CitaDTO;
use Vetcare\Dominio\CasoDeUso\AgendarCita;
use Vetcare\Dominio\CasoDeUso\AtenderCita;
use Vetcare\Dominio\CasoDeUso\CancelarCita;
use Vetcare\Dominio\CasoDeUso\ConfirmarCita;
use Vetcare\Dominio\CasoDeUso\ReprogramarCita;
use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\MascotaRepository;
use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Servicio de Aplicacion. Es la fachada que usa la Presentacion:
 *  - recibe / devuelve DTOs (nunca Entities);
 *  - invoca los Use Cases del Dominio;
 *  - traduce el resultado del Dominio a CitaDTO.
 * No contiene reglas de negocio: solo coordina y adapta.
 */
final class ServicioDeCitas
{
    public function __construct(
        private readonly AgendarCita $agendarCita,
        private readonly ConfirmarCita $confirmarCita,
        private readonly ReprogramarCita $reprogramarCita,
        private readonly CancelarCita $cancelarCita,
        private readonly AtenderCita $atenderCita,
        private readonly CitaRepository $citas,
        private readonly MascotaRepository $mascotas,
        private readonly VeterinarioRepository $veterinarios,
    ) {
    }

    public function agendar(AgendarCitaRequest $peticion): CitaDTO
    {
        $id = $this->agendarCita->ejecutar(
            $peticion->mascotaId,
            $peticion->veterinarioId,
            $peticion->inicio,
            $peticion->motivo,
        );

        return $this->ver($id);
    }

    public function confirmar(string $citaId): CitaDTO
    {
        $this->confirmarCita->ejecutar($citaId);

        return $this->ver($citaId);
    }

    public function reprogramar(string $citaId, string $nuevoInicio): CitaDTO
    {
        $this->reprogramarCita->ejecutar($citaId, $nuevoInicio);

        return $this->ver($citaId);
    }

    public function cancelar(string $citaId, string $motivo): CitaDTO
    {
        $this->cancelarCita->ejecutar($citaId, $motivo);

        return $this->ver($citaId);
    }

    public function atender(string $citaId, string $diagnostico): CitaDTO
    {
        $this->atenderCita->ejecutar($citaId, $diagnostico);

        return $this->ver($citaId);
    }

    public function ver(string $citaId): CitaDTO
    {
        $cita = $this->citas->buscarPorId($citaId)
            ?? throw new RecursoNoEncontrado("La cita \"{$citaId}\" no existe.");

        return $this->aDTO($cita);
    }

    /**
     * Listado para la capa de Presentacion, ordenado por inicio de la franja.
     *
     * @return CitaDTO[]
     */
    public function listarCitas(): array
    {
        $citas = $this->citas->listarTodas();
        usort(
            $citas,
            static fn (Cita $a, Cita $b): int => $a->franja()->inicio <=> $b->franja()->inicio,
        );

        return array_map($this->aDTO(...), $citas);
    }

    /** @return array<string, string> id => etiqueta, para los <select> del formulario. */
    public function mascotasParaSeleccion(): array
    {
        $opciones = [];
        foreach ($this->mascotas->listarTodas() as $mascota) {
            $opciones[$mascota->id] = sprintf('%s (%s)', $mascota->nombre, $mascota->especie->value);
        }

        return $opciones;
    }

    /** @return array<string, string> id => nombre. */
    public function veterinariosParaSeleccion(): array
    {
        $opciones = [];
        foreach ($this->veterinarios->listarTodos() as $veterinario) {
            $opciones[$veterinario->id] = $veterinario->nombre;
        }

        return $opciones;
    }

    /** @return array<string, string> valor => etiqueta legible. */
    public function motivosParaSeleccion(): array
    {
        $opciones = [];
        foreach (MotivoDeConsulta::cases() as $motivo) {
            $opciones[$motivo->value] = $motivo->etiqueta();
        }

        return $opciones;
    }

    private function aDTO(Cita $cita): CitaDTO
    {
        return CitaDTO::desde(
            $cita,
            $this->mascotas->buscarPorId($cita->mascotaId),
            $this->veterinarios->buscarPorId($cita->veterinarioId),
        );
    }
}
