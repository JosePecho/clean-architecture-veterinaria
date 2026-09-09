<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento;

/**
 * Escribe en un archivo de texto lo que anuncian los Domain Events.
 * Es una de las tantas cosas que "escuchan" al Dominio sin que el Dominio lo sepa.
 */
final class RegistroDeNotificaciones
{
    public function __construct(private readonly string $ruta)
    {
    }

    public function registrar(string $mensaje): void
    {
        $directorio = dirname($this->ruta);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $linea = sprintf('[%s] %s', date('Y-m-d H:i:s'), $mensaje);
        file_put_contents($this->ruta, $linea . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return string[] Las ultimas $limite lineas, de la mas reciente a la mas antigua.
     */
    public function ultimas(int $limite = 12): array
    {
        if (!is_file($this->ruta)) {
            return [];
        }

        $lineas = preg_split('/\R/', (string) file_get_contents($this->ruta)) ?: [];
        $lineas = array_values(array_filter(
            $lineas,
            static fn (string $linea): bool => trim($linea) !== '',
        ));

        return array_slice(array_reverse($lineas), 0, $limite);
    }
}
