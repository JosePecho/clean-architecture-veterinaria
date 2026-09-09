<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

use DateTimeImmutable;
use Vetcare\Dominio\Contrato\MascotaRepository;
use Vetcare\Dominio\Modelo\Mascota;
use Vetcare\Dominio\ValueObject\Especie;

/**
 * Persistencia en archivo de texto (una linea por mascota):
 *   id|nombre|especie|fechaNacimiento(Y-m-d)|duenoId
 */
final class MascotaArchivoRepository implements MascotaRepository
{
    private readonly Hoja $hoja;

    public function __construct(string $directorioDatos)
    {
        $this->hoja = new Hoja(rtrim($directorioDatos, "/\\") . '/mascotas.txt');
    }

    public function buscarPorId(string $id): ?Mascota
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

    public function guardar(Mascota $mascota): void
    {
        $this->hoja->guardarRegistro($mascota->id, $this->aLinea($mascota));
    }

    private function aLinea(Mascota $mascota): string
    {
        return implode('|', [
            $mascota->id,
            $mascota->nombre,
            $mascota->especie->value,
            $mascota->fechaNacimiento->format('Y-m-d'),
            $mascota->duenoId,
        ]);
    }

    private function deLinea(string $linea): Mascota
    {
        [$id, $nombre, $especie, $nacimiento, $duenoId] = explode('|', $linea, 5);

        return new Mascota(
            $id,
            $nombre,
            Especie::from($especie),
            new DateTimeImmutable($nacimiento),
            $duenoId,
        );
    }
}
