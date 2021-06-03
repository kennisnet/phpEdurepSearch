<?php

namespace Tests\Kennisnet\Edurep;

use Kennisnet\ECK\EckRecord;
use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\Edurep\Normalizer\CatalogNormalizer;
use Kennisnet\Edurep\Normalizer\EdurepResponseNormalizer;
use Kennisnet\Edurep\Normalizer\NLLOMRecordNormalizer;
use Kennisnet\Edurep\Serializer\DefaultResponseSerializer;
use Kennisnet\Edurep\Serializer\NLLOMResponseSerializer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function testNormalizeEckSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/eckResponse.xml');
        $normalizer = new EdurepResponseNormalizer(new DefaultResponseSerializer(), new CatalogNormalizer());
        $result = $normalizer->unSerialize($response,'xml');
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EckRecord::class, get_class(array_shift($records)));
    }

    public function testNormalizeLomSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/lomResponse.xml');
        $normalizer = new EdurepResponseNormalizer(new NLLOMResponseSerializer(), new NLLOMRecordNormalizer());
        $result = $normalizer->unSerialize($response);
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EdurepRecord::class, get_class(array_shift($records)));
    }

    public function testDefaultLomSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/default.xml');
        $normalizer = new EdurepResponseNormalizer(new NLLOMResponseSerializer(), new NLLOMRecordNormalizer());
        $result = $normalizer->unSerialize($response);
        $this->assertNotEmpty($result->getRecords());

        $this->assertEquals($result->getNumberOfRecords(), 42119);
    }

    public function testDrilldownSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/drilldown.xml');
        $normalizer = new EdurepResponseNormalizer(new NLLOMResponseSerializer(), new NLLOMRecordNormalizer());
        $result = $normalizer->unSerialize($response);
        $this->assertEmpty($result->getRecords());

        $this->assertEquals(2, count($result->getDrilldown()->getNavigators()));
    }
}
