<?php

declare(strict_types=1);

namespace Vetcare\Dominio\Excepcion;

use DomainException;

/**
 * Se lanza cuando una operacion dejaria al negocio en un estado invalido.
 * Es el mecanismo con el que las Business Rules del documento se hacen cumplir.
 */
final class ReglaDeNegocioViolada extends DomainException
{
}
