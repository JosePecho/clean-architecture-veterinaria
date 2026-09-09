<?php

declare(strict_types=1);

namespace Vetcare\Presentacion\Web;

use InvalidArgumentException;
use Vetcare\Aplicacion\DTO\AgendarCitaRequest;
use Vetcare\Aplicacion\Servicio\ServicioDeCitas;
use Vetcare\Dominio\Excepcion\RecursoNoEncontrado;
use Vetcare\Dominio\Excepcion\ReglaDeNegocioViolada;

/**
 * Controlador de la capa de Presentacion (web). Traduce el $_POST crudo a llamadas
 * al Servicio de Aplicacion, captura los errores del Dominio y devuelve un mensaje
 * "flash" para que el front controller lo muestre tras el redirect (patron PRG).
 */
final class ControladorWebDeCitas
{
    public function __construct(private readonly ServicioDeCitas $servicio)
    {
    }

    /**
     * @param array<string, mixed> $datos  normalmente $_POST
     * @return array{tipo: string, mensaje: string}
     */
    public function procesar(string $accion, array $datos): array
    {
        try {
            return match ($accion) {
                'agendar' => $this->ok(sprintf(
                    'Cita %s agendada.',
                    $this->servicio->agendar(new AgendarCitaRequest(
                        $this->campo($datos, 'mascota'),
                        $this->campo($datos, 'veterinario'),
                        $this->campo($datos, 'inicio'),
                        $this->campo($datos, 'motivo'),
                    ))->id,
                )),
                'confirmar' => $this->hecho(
                    fn () => $this->servicio->confirmar($this->campo($datos, 'id')),
                    'Cita confirmada.',
                ),
                'reprogramar' => $this->hecho(
                    fn () => $this->servicio->reprogramar($this->campo($datos, 'id'), $this->campo($datos, 'inicio')),
                    'Cita reprogramada.',
                ),
                'atender' => $this->hecho(
                    fn () => $this->servicio->atender($this->campo($datos, 'id'), $this->campo($datos, 'diagnostico')),
                    'Cita atendida y cerrada.',
                ),
                'cancelar' => $this->hecho(
                    fn () => $this->servicio->cancelar($this->campo($datos, 'id'), $this->campo($datos, 'motivo')),
                    'Cita cancelada.',
                ),
                default => $this->error("Accion desconocida: \"{$accion}\"."),
            };
        } catch (ReglaDeNegocioViolada | RecursoNoEncontrado | InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }
    }

    private function hecho(callable $accion, string $mensaje): array
    {
        $accion();

        return $this->ok($mensaje);
    }

    /** @param array<string, mixed> $datos */
    private function campo(array $datos, string $clave): string
    {
        $valor = trim((string) ($datos[$clave] ?? ''));
        if ($valor === '') {
            throw new InvalidArgumentException("Falta completar el campo \"{$clave}\".");
        }

        return $valor;
    }

    private function ok(string $mensaje): array
    {
        return ['tipo' => 'ok', 'mensaje' => $mensaje];
    }

    private function error(string $mensaje): array
    {
        return ['tipo' => 'error', 'mensaje' => $mensaje];
    }
}
