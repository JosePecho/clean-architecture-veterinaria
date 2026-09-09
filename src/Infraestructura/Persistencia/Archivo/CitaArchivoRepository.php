<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

use DateTimeImmutable;
use Vetcare\Dominio\Contrato\CitaRepository;
use Vetcare\Dominio\Modelo\Cita;
use Vetcare\Dominio\ValueObject\Diagnostico;
use Vetcare\Dominio\ValueObject\Dinero;
use Vetcare\Dominio\ValueObject\EstadoCita;
use Vetcare\Dominio\ValueObject\FranjaHoraria;
use Vetcare\Dominio\ValueObject\MotivoDeConsulta;

/**
 * Persistencia en archivo de texto (una linea por cita):
 *   id|mascotaId|veterinarioId|inicio|fin|motivo|costoCentimos|moneda|estado|diagnostico
 * Fechas en formato "Y-m-d H:i:s". El diagnostico puede ir vacio.
 */
final class CitaArchivoRepository implements CitaRepository
{
    private readonly Hoja $hoja;

    public function __construct(string $directorioDatos)
    {
        $this->hoja = new Hoja(rtrim($directorioDatos, "/\\") . '/citas.txt');
    }

    public function guardar(Cita $cita): void
    {
        $this->hoja->guardarRegistro($cita->id, $this->aLinea($cita));
    }

    public function buscarPorId(string $id): ?Cita
    {
        foreach ($this->hoja->leerLineas() as $linea) {
            if (str_starts_with($linea, $id . '|')) {
                return $this->deLinea($linea);
            }
        }

        return null;
    }

    public function listarTodas(): array
    {
        return array_map($this->deLinea(...), $this->hoja->leerLineas());
    }

    public function listarDeVeterinarioEnFecha(string $veterinarioId, DateTimeImmutable $fecha): array
    {
        $dia = $fecha->format('Y-m-d');

        return array_values(array_filter(
            $this->listarTodas(),
            static fn (Cita $cita): bool => $cita->veterinarioId === $veterinarioId
                && $cita->franja()->inicio->format('Y-m-d') === $dia,
        ));
    }

    public function siguienteId(): string
    {
        return sprintf('CITA-%03d', count($this->hoja->leerLineas()) + 1);
    }

    private function aLinea(Cita $cita): string
    {
        $franja = $cita->franja();
        $diagnostico = $cita->diagnostico();

        return implode('|', [
            $cita->id,
            $cita->mascotaId,
            $cita->veterinarioId,
            $franja->inicio->format('Y-m-d H:i:s'),
            $franja->fin->format('Y-m-d H:i:s'),
            $cita->motivo->value,
            (string) $cita->costo->centimos,
            $cita->costo->moneda,
            $cita->estado()->value,
            $diagnostico !== null ? $this->limpiar((string) $diagnostico) : '',
        ]);
    }

    private function deLinea(string $linea): Cita
    {
        [$id, $mascotaId, $veterinarioId, $inicio, $fin, $motivo, $centimos, $moneda, $estado, $diagnostico]
            = explode('|', $linea, 10);

        return Cita::rehidratar(
            $id,
            $mascotaId,
            $veterinarioId,
            new FranjaHoraria(new DateTimeImmutable($inicio), new DateTimeImmutable($fin)),
            MotivoDeConsulta::from($motivo),
            Dinero::deCentimos((int) $centimos, $moneda),
            EstadoCita::from($estado),
            $diagnostico === '' ? null : new Diagnostico($diagnostico),
        );
    }

    /** El separador "|" y los saltos de linea no pueden vivir dentro de un campo. */
    private function limpiar(string $texto): string
    {
        return trim(str_replace(['|', "\r", "\n"], ' ', $texto));
    }
}
