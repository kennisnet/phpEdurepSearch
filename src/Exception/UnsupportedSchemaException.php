<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Exception;

final class UnsupportedSchemaException extends \Exception
{
    public static function becauseTheSchemaIsNotSupportedByTheNormalizer(string $schema, string $normalizerClass): self
    {
        return new self(sprintf('The schema \'%s\' is not supported by the selected normalizer \'%s\'', $schema, $normalizerClass));
    }
}
