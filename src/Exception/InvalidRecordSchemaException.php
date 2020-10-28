<?php

declare(strict_types=1);

namespace Kennisnet\Edurep\Exception;


use Exception;

class InvalidRecordSchemaException extends Exception
{
    public static function create($givenSchema): self
    {
        return new self('Invalid schema given. Allowed schema\'s [...]. ' . $givenSchema . ' given');
    }
}
