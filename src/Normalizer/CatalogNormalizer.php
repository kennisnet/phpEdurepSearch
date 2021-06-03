<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Normalizer;

use Kennisnet\ECK\EckRecordSchemaTypes;
use Kennisnet\ECK\RecordsNormalizer;
use Kennisnet\Edurep\Exception\UnsupportedSchemaException;

final class CatalogNormalizer implements RecordNormalizer
{
    /**
     * @throws UnsupportedSchemaException
     */
    public function normalize(array $data, string $schema): array
    {
        if (!self::supportsSchema($schema)){
            throw UnsupportedSchemaException::becauseTheSchemaIsNotSupportedByTheNormalizer($schema, self::class);
        }

        return (new RecordsNormalizer())->normalize($data, $schema);
    }

    private static function supportsSchema(string $schema): bool
    {
        return in_array($schema, [EckRecordSchemaTypes::ECKCS_2_3]);
    }
}
