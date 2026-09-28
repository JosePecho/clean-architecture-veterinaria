<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

use Vetcare\Dominio\Contrato\FichaMascotaRepositorio;
use Vetcare\Dominio\Modelo\FichaMascota;

/**
 * Persistencia en archivo de texto plano (un JSON por linea) en data/fichas_mascotas.txt.
 * A diferencia de los repositorios heredados que usan formato delimitado por pipe '|',
 * este repositorio almacena cada registro como un objeto JSON individual por linea.
 */
final class FichaMascotaArchivoRepository implements FichaMascotaRepositorio
{
    private readonly string $ruta;

    public function __construct(string $directorioDatos)
    {
        $this->ruta = rtrim($directorioDatos, "/\\") . '/fichas_mascotas.txt';
    }

    public function guardar(FichaMascota $ficha): void
    {
        $datos = $this->leerCrudo();
        $datos[$ficha->mascotaId] = [
            'mascotaId' => $ficha->mascotaId,
            'sexo' => $ficha->sexo,
            'pesoKg' => $ficha->pesoKg,
            'esterilizado' => $ficha->esterilizado,
            'colorPelaje' => $ficha->colorPelaje,
            'microchip' => $ficha->microchip,
            'alergias' => $ficha->alergias,
            'fotoUrl' => $ficha->fotoUrl,
        ];

        $this->escribir($datos);
    }

    public function buscar(string $mascotaId): ?FichaMascota
    {
        $datos = $this->leerCrudo();
        if (!isset($datos[$mascotaId])) {
            return null;
        }

        return $this->hidratar($datos[$mascotaId]);
    }

    /**
     * @return array<string, FichaMascota>
     */
    public function todos(): array
    {
        $datos = $this->leerCrudo();
        $resultado = [];
        foreach ($datos as $id => $registro) {
            $resultado[$id] = $this->hidratar($registro);
        }

        return $resultado;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function leerCrudo(): array
    {
        if (!is_file($this->ruta)) {
            return [];
        }

        $contenido = file_get_contents($this->ruta);
        if ($contenido === false || trim($contenido) === '') {
            return [];
        }

        $lineas = preg_split('/\R/', $contenido) ?: [];
        $registros = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            $decodificado = json_decode($linea, true);
            if (is_array($decodificado) && isset($decodificado['mascotaId'])) {
                $registros[(string) $decodificado['mascotaId']] = $decodificado;
            }
        }

        return $registros;
    }

    /**
     * @param array<string, array<string, mixed>> $datos
     */
    private function escribir(array $datos): void
    {
        $directorio = dirname($this->ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $lineas = [];
        foreach ($datos as $registro) {
            $lineas[] = json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        file_put_contents(
            $this->ruta,
            $lineas === [] ? '' : implode(PHP_EOL, $lineas) . PHP_EOL,
            LOCK_EX,
        );
    }

    /**
     * @param array<string, mixed> $d
     */
    private function hidratar(array $d): FichaMascota
    {
        return new FichaMascota(
            mascotaId: (string) ($d['mascotaId'] ?? ''),
            sexo: (string) ($d['sexo'] ?? 'macho'),
            pesoKg: (float) ($d['pesoKg'] ?? 0.0),
            esterilizado: (bool) ($d['esterilizado'] ?? false),
            colorPelaje: (string) ($d['colorPelaje'] ?? ''),
            microchip: isset($d['microchip']) && $d['microchip'] !== '' ? (string) $d['microchip'] : null,
            alergias: isset($d['alergias']) && $d['alergias'] !== '' ? (string) $d['alergias'] : null,
            fotoUrl: isset($d['fotoUrl']) && $d['fotoUrl'] !== '' ? (string) $d['fotoUrl'] : null,
        );
    }
}
