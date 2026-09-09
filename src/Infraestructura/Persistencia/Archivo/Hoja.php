<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Persistencia\Archivo;

/**
 * Utilidad de Infraestructura: una "hoja" de texto plano, una linea por registro.
 * Encapsula la lectura y escritura del archivo para que cada repositorio solo se
 * ocupe de convertir Entity <-> linea.
 */
final class Hoja
{
    public function __construct(private readonly string $ruta)
    {
    }

    /** @return string[] Lineas no vacias del archivo (o [] si no existe). */
    public function leerLineas(): array
    {
        if (!is_file($this->ruta)) {
            return [];
        }

        $contenido = file_get_contents($this->ruta);
        if ($contenido === false || trim($contenido) === '') {
            return [];
        }

        $lineas = preg_split('/\R/', $contenido) ?: [];

        return array_values(array_filter(
            $lineas,
            static fn (string $linea): bool => trim($linea) !== '',
        ));
    }

    /** @param string[] $lineas */
    public function escribirLineas(array $lineas): void
    {
        $directorio = dirname($this->ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        file_put_contents($this->ruta, implode(PHP_EOL, $lineas) . PHP_EOL, LOCK_EX);
    }

    /** Reemplaza (o agrega) la linea cuyo primer campo, separado por "|", es $id. */
    public function guardarRegistro(string $id, string $linea): void
    {
        $resultado = [];
        $reemplazado = false;

        foreach ($this->leerLineas() as $existente) {
            if (str_starts_with($existente, $id . '|')) {
                $resultado[] = $linea;
                $reemplazado = true;
            } else {
                $resultado[] = $existente;
            }
        }

        if (!$reemplazado) {
            $resultado[] = $linea;
        }

        $this->escribirLineas($resultado);
    }
}
