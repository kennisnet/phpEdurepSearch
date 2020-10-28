<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 11:07
 */
declare(strict_types=1);

namespace Kennisnet\Edurep\Exception;


class InvalidRecordSchemaException extends \Exception
{
    public static function create($givenSchema): self
    {
        return new self('Invalid schema given. Allowed schema\'s [...]. ' . $givenSchema . ' given');
    }
}
