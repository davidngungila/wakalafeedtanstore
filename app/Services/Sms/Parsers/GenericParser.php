<?php

namespace App\Services\Sms\Parsers;

class GenericParser
{
    public function templates(): array
    {
        // Generic has no approved templates - strict: unknown/unrecognized SMS never becomes a transaction.
        // It will be stored as NEEDS_REVIEW / Stored.
        return [];
    }
}
