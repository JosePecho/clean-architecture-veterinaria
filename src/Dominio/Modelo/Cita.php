<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Modelo;

use DateTimeImmutable;
use Vetcare\Dominio\Evento\CitaAgendadaEvent;
use Vetcare\Dominio\Evento\CitaCanceladaEvent;
use Vetcare\Dominio\Evento\CitaCompletadaEvent;
use Vetcare\Dominio\Evento\CitaReprogramadaEvent;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;
use Vetcare\Dominio\Modelo\Comun\RegistraEventos;
use Vetcare\Dominio\ValueObject\Diagnostico;
use Vetcare\Dominio\ValueObject\Dinero;
use Vetcare\Dominio\ValueObject\EstadoCita;
use Vetcare\Dominio\ValueObject\FranjaHoraria;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Entity raiz del sistema. Concentra sus Business Rules (como el "Pedido" del documento):
 *  - no se agenda en el pasado ni fuera de la jornada del veterinario;
 *  - el estado solo avanza, nunca retrocede;
 *  - reprogramar exige 24 h de anticipacion;
 *  - una cita en estado final no se toca.
 * Coordinar VARIAS entidades (solapamientos de agenda, tarifa) NO es su trabajo:
 * eso vive en los Domain Services y en los Use Cases.
 */
final class Cita
{
    use RegistraEventos;

    private const HORAS_MINIMAS_PARA_REPROGRAMAR = 24;

    private function __construct(
        public readonly string $id,
        public readonly string $mascotaId,
        public readonly string $veterinarioId,
        private FranjaHoraria $franja,
        public readonly MotivoDeConsulta $motivo,
        public readonly Dinero $costo,
        private EstadoCita $estado,
        private ?Diagnostico $diagnostico,
    ) {
    }

    /**
     * Factory de creacion. Aqui viven las reglas que la cita puede verificar con lo
     * que recibe (mascota, veterinario y franja). El resto lo valida el Use Case.
     * Emite el Domain Event CitaAgendadaEvent.
     */
    public static function agendar(
        string $id,
        Mascota $mascota,
        Veterinario $veterinario,
        FranjaHoraria $franja,
        MotivoDeConsulta $motivo,
        Dinero $costo,
        DateTimeImmutable $ahora,
    ): self {
        if ($franja->empiezaAntesDe($ahora)) {
            throw new ReglaDeNegocioViolada('No se puede agendar una cita en el pasado.');
        }

        if (!$veterinario->atiendeEspecie($mascota->especie)) {
            throw new ReglaDeNegocioViolada(sprintf(
                'El veterinario %s no atiende la especie "%s".',
                $veterinario->nombre,
                $mascota->especie->value,
            ));
        }

        if (!$veterinario->trabajaEn($franja)) {
            throw new ReglaDeNegocioViolada('La franja esta fuera de la jornada del veterinario.');
        }

        $cita = new self(
            $id,
            $mascota->id,
            $veterinario->id,
            $franja,
            $motivo,
            $costo,
            EstadoCita::PROGRAMADA,
            null,
        );
        $cita->registrarEvento(
            new CitaAgendadaEvent($cita->id, $cita->mascotaId, $cita->veterinarioId, (string) $franja, $ahora)
        );

        return $cita;
    }

    /**
     * Reconstruye una Cita desde el almacenamiento. NO valida reglas de creacion
     * ni emite Domain Events: el estado guardado ya es un estado valido del pasado.
     * Solo lo deben usar los repositorios.
     */
    public static function rehidratar(
        string $id,
        string $mascotaId,
        string $veterinarioId,
        FranjaHoraria $franja,
        MotivoDeConsulta $motivo,
        Dinero $costo,
        EstadoCita $estado,
        ?Diagnostico $diagnostico,
    ): self {
        return new self($id, $mascotaId, $veterinarioId, $franja, $motivo, $costo, $estado, $diagnostico);
    }

    public function franja(): FranjaHoraria
    {
        return $this->franja;
    }

    public function estado(): EstadoCita
    {
        return $this->estado;
    }

    public function diagnostico(): ?Diagnostico
    {
        return $this->diagnostico;
    }

    public function confirmar(): void
    {
        $this->exigirTransicion(EstadoCita::CONFIRMADA);
        $this->estado = EstadoCita::CONFIRMADA;
    }

    public function reprogramar(FranjaHoraria $nueva, DateTimeImmutable $ahora): void
    {
        if ($this->estado->esFinal()) {
            throw new ReglaDeNegocioViolada(
                "Una cita en estado \"{$this->estado->value}\" ya no se puede reprogramar."
            );
        }

        $horasDeAviso = ($this->franja->inicio->getTimestamp() - $ahora->getTimestamp()) / 3600;
        if ($horasDeAviso < self::HORAS_MINIMAS_PARA_REPROGRAMAR) {
            throw new ReglaDeNegocioViolada(sprintf(
                'Reprogramar exige al menos %d h de anticipacion (faltan %.1f h).',
                self::HORAS_MINIMAS_PARA_REPROGRAMAR,
                max($horasDeAviso, 0),
            ));
        }

        if ($nueva->empiezaAntesDe($ahora)) {
            throw new ReglaDeNegocioViolada('La nueva franja esta en el pasado.');
        }

        $anterior = (string) $this->franja;
        $this->franja = $nueva;
        $this->estado = EstadoCita::PROGRAMADA;
        $this->registrarEvento(
            new CitaReprogramadaEvent($this->id, $anterior, (string) $nueva, $ahora)
        );
    }

    public function iniciarAtencion(): void
    {
        $this->exigirTransicion(EstadoCita::EN_ATENCION);
        $this->estado = EstadoCita::EN_ATENCION;
    }

    public function completar(Diagnostico $diagnostico, DateTimeImmutable $ahora): void
    {
        $this->exigirTransicion(EstadoCita::COMPLETADA);
        $this->estado = EstadoCita::COMPLETADA;
        $this->diagnostico = $diagnostico;
        $this->registrarEvento(
            new CitaCompletadaEvent($this->id, $this->mascotaId, (string) $diagnostico, $ahora)
        );
    }

    public function cancelar(string $motivo, DateTimeImmutable $ahora): void
    {
        $this->exigirTransicion(EstadoCita::CANCELADA);
        $this->estado = EstadoCita::CANCELADA;
        $this->registrarEvento(new CitaCanceladaEvent($this->id, $motivo, $ahora));
    }

    private function exigirTransicion(EstadoCita $destino): void
    {
        if (!$this->estado->puedeAvanzarA($destino)) {
            throw new ReglaDeNegocioViolada(sprintf(
                'Transicion de estado invalida: "%s" -> "%s".',
                $this->estado->value,
                $destino->value,
            ));
        }
    }
}
