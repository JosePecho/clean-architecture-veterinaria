<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

use Vetcare\Dominio\Contrato\VeterinarioRepository;
use Vetcare\Dominio\Modelo\Veterinario;
use Vetcare\Dominio\ValueObject\Especie;

/**
 * Persistencia en archivo de texto (una linea por veterinario):
 *   id|nombre|especiesQueAtiende(coma)|horaInicio|horaFin
 * El campo de especies vacio significa "atiende todas".
 */
final class VeterinarioArchivoRepository implements VeterinarioRepository
{
    private readonly Hoja $hoja;

    public function __construct(string $directorioDatos)
    {
        $this->hoja = new Hoja(rtrim($directorioDatos, "/\\") . '/veterinarios.txt');
    }

    public function buscarPorId(string $id): ?Veterinario
    {
        foreach ($this->hoja->leerLineas() as $linea) {
            if (str_starts_with($linea, $id . '|')) {
                return $this->deLinea($linea);
            }
        }

        return null;
    }

    public function listarTodos(): array
    {
        return array_map($this->deLinea(...), $this->hoja->leerLineas());
    }

    public function guardar(Veterinario $veterinario): void
    {
        $this->hoja->guardarRegistro($veterinario->id, $this->aLinea($veterinario));
    }

    private function aLinea(Veterinario $veterinario): string
    {
        $especies = array_map(
            static fn (Especie $especie): string => $especie->value,
            $veterinario->especiesQueAtiende(),
        );

        return implode('|', [
            $veterinario->id,
            $veterinario->nombre,
            implode(',', $especies),
            (string) $veterinario->horaInicioJornada(),
            (string) $veterinario->horaFinJornada(),
        ]);
    }

    private function deLinea(string $linea): Veterinario
    {
        [$id, $nombre, $especiesCsv, $horaInicio, $horaFin] = explode('|', $linea, 5);

        $especies = $especiesCsv === ''
            ? []
            : array_map(
                static fn (string $valor): Especie => Especie::from(trim($valor)),
                explode(',', $especiesCsv),
            );

        return new Veterinario($id, $nombre, $especies, (int) $horaInicio, (int) $horaFin);
    }
}
