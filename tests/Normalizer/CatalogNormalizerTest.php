<?php
declare(strict_types=1);

namespace Tests\Kennisnet\Edurep\Normalizer;

use Kennisnet\ECK\EckRecord;
use Kennisnet\ECK\EckRecordSchemaTypes;
use Kennisnet\Edurep\Exception\UnsupportedSchemaException;
use Kennisnet\Edurep\Normalizer\CatalogNormalizer;
use PHPUnit\Framework\TestCase;

final class CatalogNormalizerTest extends TestCase
{
    public function test_it_throws_an_unsupported_schema_exception_when_a_not_supported_scheme_is_used(): void
    {
        self::expectException(UnsupportedSchemaException::class);
        (new CatalogNormalizer())->normalize([], EckRecordSchemaTypes::ECKCS_2_2);
    }

    public function test_it_normalizes_to_eck_records(): void
    {
        $result = (new CatalogNormalizer())->normalize(
            [
                'id' => [
                    'Entry' => [
                        'Title' => 'title',
                    ]
                ]
            ], EckRecordSchemaTypes::ECKCS_2_3
        );
        $firstResult = array_shift($result);
        self::assertInstanceOf(EckRecord::class, $firstResult);
    }
}
