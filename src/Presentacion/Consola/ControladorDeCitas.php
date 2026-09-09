<?php

declare(strict_types=1);

namespace Vetcare\Presentacion\Consola;

use InvalidArgumentException;
use Vetcare\Aplicacion\DTO\AgendarCitaRequest;
use Vetcare\Aplicacion\Servicio\ServicioDeCitas;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;

/**
 * Controlador de la capa de Presentacion. Traduce "entrada cruda" (arrays, como
 * el cuerpo de un request HTTP) a DTOs, llama al Servicio de Aplicacion y entrega
 * el resultado al Presentador. Captura los errores del Dominio y los muestra.
 */
final class ControladorDeCitas
{
    public function __construct(
        private readonly ServicioDeCitas $servicio,
        private readonly PresentadorDeCitas $vista,
    ) {
    }

    /**
     * @param array{mascota: string, veterinario: string, inicio: string, motivo: string} $entrada
     */
    public function agendar(array $entrada): ?string
    {
        try {
            $cita = $this->servicio->agendar(new AgendarCitaRequest(
                $entrada['mascota'],
                $entrada['veterinario'],
                $entrada['inicio'],
                $entrada['motivo'],
            ));
            $this->vista->cita($cita);

            return $cita->id;
        } catch (ReglaDeNegocioViolada | RecursoNoEncontrado | InvalidArgumentException $e) {
            $this->vista->rechazo($e->getMessage());

            return null;
        }
    }

    public function confirmar(string $citaId): void
    {
        $this->ejecutar(fn () => $this->servicio->confirmar($citaId));
    }

    public function reprogramar(string $citaId, string $nuevoInicio): void
    {
        $this->ejecutar(fn () => $this->servicio->reprogramar($citaId, $nuevoInicio));
    }

    public function cancelar(string $citaId, string $motivo): void
    {
        $this->ejecutar(fn () => $this->servicio->cancelar($citaId, $motivo));
    }

    public function atender(string $citaId, string $diagnostico): void
    {
        $this->ejecutar(fn () => $this->servicio->atender($citaId, $diagnostico));
    }

    private function ejecutar(callable $accion): void
    {
        try {
            $this->vista->cita($accion());
        } catch (ReglaDeNegocioViolada | RecursoNoEncontrado | InvalidArgumentException $e) {
            $this->vista->rechazo($e->getMessage());
        }
    }
}
