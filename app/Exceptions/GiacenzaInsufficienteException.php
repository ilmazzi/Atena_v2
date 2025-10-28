<?php

namespace App\Exceptions;

use Exception;

class GiacenzaInsufficienteException extends Exception
{
    public static function forArticolo(string $codice, int $richiesta, int $disponibile): self
    {
        return new self(
            "Giacenza insufficiente per l'articolo {$codice}. Richiesta: {$richiesta}, Disponibile: {$disponibile}"
        );
    }
}


