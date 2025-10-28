<?php

namespace App\Models\ValueObjects;

class CodiceArticolo
{
    public function __construct(
        public readonly int $magazzinoId,
        public readonly int $numero
    ) {}

    public static function fromString(string $codice): self
    {
        // Formato: M-XXXXX dove M è il magazzino e XXXXX è il numero
        if (!preg_match('/^(\d+)-(\d+)$/', $codice, $matches)) {
            throw new \InvalidArgumentException("Formato codice non valido: {$codice}");
        }

        return new self(
            magazzinoId: (int) $matches[1],
            numero: (int) $matches[2]
        );
    }

    public function toString(): string
    {
        return "{$this->magazzinoId}-" . str_pad($this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getCarico(): int
    {
        return $this->numero;
    }

    public function getMagazzinoId(): int
    {
        return $this->magazzinoId;
    }
}
