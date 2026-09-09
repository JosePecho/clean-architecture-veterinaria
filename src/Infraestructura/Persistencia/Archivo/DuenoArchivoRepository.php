<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

use Vetcare\Dominio\Contrato\DuenoRepository;
use Vetcare\Dominio\Modelo\Dueno;
use Vetcare\Dominio\ValueObject\Email;
use Vetcare\Dominio\ValueObject\Telefono;

/**
 * Persistencia en archivo de texto (una linea por dueno):
 *   id|nombre|telefono|email
 */
final class DuenoArchivoRepository implements DuenoRepository
{
    private readonly Hoja $hoja;

    public function __construct(string $directorioDatos)
    {
        $this->hoja = new Hoja(rtrim($directorioDatos, "/\\") . '/duenos.txt');
    }

    public function buscarPorId(string $id): ?Dueno
    {
        foreach ($this->hoja->leerLineas() as $linea) {
            if (str_starts_with($linea, $id . '|')) {
                return $this->deLinea($linea);
            }
        }

        return null;
    }

    public function guardar(Dueno $dueno): void
    {
        $this->hoja->guardarRegistro($dueno->id, $this->aLinea($dueno));
    }

    private function aLinea(Dueno $dueno): string
    {
        return implode('|', [
            $dueno->id,
            $dueno->nombre,
            (string) $dueno->telefono,
            (string) $dueno->email,
        ]);
    }

    private function deLinea(string $linea): Dueno
    {
        [$id, $nombre, $telefono, $email] = explode('|', $linea, 4);

        return new Dueno($id, $nombre, new Telefono($telefono), new Email($email));
    }
}
