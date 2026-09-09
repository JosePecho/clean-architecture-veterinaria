<?php

declare(strict_types=1);

namespace Vetcare\Infraestructura\Evento;

use Vetcare\Dominio\Contrato\DespachadorDeEventos;
use Vetcare\Dominio\Evento\EventoDeDominio;

/**
 * Implementacion simple del puerto DespachadorDeEventos: un registro de
 * suscriptores en memoria. En produccion podria ser un bus real, una cola, etc.
 */
final class DespachadorEnMemoria implements DespachadorDeEventos
{
    /** @var array<string, callable[]> */
    private array $suscriptores = [];

    public function suscribir(string $nombreEvento, callable $manejador): void
    {
        $this->suscriptores[$nombreEvento][] = $manejador;
    }

    public function despachar(array $eventos): void
    {
        foreach ($eventos as $evento) {
            assert($evento instanceof EventoDeDominio);

            foreach ($this->suscriptores[$evento->nombre()] ?? [] as $manejador) {
                $manejador($evento);
            }
        }
    }
}
