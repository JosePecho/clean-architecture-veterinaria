<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Excepcion;

use RuntimeException;

/** Se lanza cuando un Use Case pide por id una Entity que no existe. */
final class RecursoNoEncontrado extends RuntimeException
{
}
