<?php

declare(strict_types=1);

namespace Vetcare\Aplicacion\Servicio;

use RuntimeException;
use Vetcare\Aplicacion\DTO\AgendarCitaRequest;
use Vetcare\Aplicacion\DTO\CitaDTO;
use Vetcare\Aplicacion\DTO\MascotaDTO;
use Vetcare\Dominio\CasoDeUso\AgendarCita;
use Vetcare\Dominio\CasoDeUso\AtenderCita;
use Vetcare\Dominio\CasoDeUso\CancelarCita;
use Vetcare\Dominio\CasoDeUso\ConfirmarCita;
use Vetcare\Dominio\CasoDeUso\RegistrarFichaDeMascota;
use Vetcare\Dominio\CasoDeUso\ReprogramarCita;
use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Contrato\FichaMascotaRepositorio;
use Vetcare\Dominio\Contrato\MascotaRepository;
use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\Modelo\FichaMascota;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Servicio de Aplicacion. Es la fachada que usa la Presentacion:
 *  - recibe / devuelve DTOs (nunca Entities);
 *  - invoca los Use Cases del Dominio;
 *  - traduce el resultado del Dominio a CitaDTO / MascotaDTO.
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
        private readonly ?FichaMascotaRepositorio $fichas = null,
        private readonly ?RegistrarFichaDeMascota $registrarFicha = null,
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

    /**
     * @return array<string, MascotaDTO> id => MascotaDTO
     */
    public function mascotasParaSeleccion(): array
    {
        $resultado = [];
        foreach ($this->mascotas->listarTodas() as $mascota) {
            $ficha = $this->fichas?->buscar($mascota->id);
            if ($ficha !== null) {
                $resultado[$mascota->id] = $this->convertirFichaADTO($ficha, $mascota->id, $mascota->nombre);
            } else {
                $resultado[$mascota->id] = new MascotaDTO(
                    id: $mascota->id,
                    nombre: $mascota->nombre,
                    sexo: '',
                    pesoKg: 0.0,
                    esterilizado: false,
                    colorPelaje: '',
                    microchip: null,
                    alergias: null,
                    fotoUrl: null,
                );
            }
        }

        return $resultado;
    }

    /**
     * @param array<string, mixed> $entrada
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $archivo
     */
    public function registrarFichaDeMascota(string $mascotaId, array $entrada, ?array $archivo): void
    {
        if ($this->registrarFicha === null) {
            throw new RuntimeException('El caso de uso RegistrarFichaDeMascota no esta configurado.');
        }

        $mascota = $this->mascotas->buscarPorId($mascotaId);
        if ($mascota === null) {
            throw new RecursoNoEncontrado("La mascota \"{$mascotaId}\" no existe.");
        }

        $this->registrarFicha->ejecutar($mascotaId, $entrada, $archivo);
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

    private function convertirFichaADTO(FichaMascota $ficha, string $id, string $nombre): MascotaDTO
    {
        return new MascotaDTO(
            id: $id,
            nombre: $nombre,
            sexo: $ficha->sexo,
            pesoKg: $ficha->pesoKg,
            esterilizado: $ficha->esterilizado,
            colorPelaje: $ficha->colorPelaje,
            microchip: $ficha->microchip,
            alergias: $ficha->alergias,
            fotoUrl: $ficha->fotoUrl,
        );
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
