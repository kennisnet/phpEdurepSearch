<?php

use Kennisnet\Edurep\EdurepResponseNormalizer;
use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\Edurep\Model\SearchResult;
use Kennisnet\Edurep\Normalizer\NLLOMRecordNormalizer;
use Kennisnet\Edurep\Serializer\DefaultResponseSerializer;
use Kennisnet\Edurep\Serializer\NLLOMResponseSerializer;

class NormalizerTest extends \PHPUnit\Framework\TestCase
{
    public function testNormalizeEckSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/eckResponse.xml');
        /** @var \Kennisnet\Edurep\Normalizer $normalizer */
        $normalizer = new EdurepResponseNormalizer(new DefaultResponseSerializer(), new \Kennisnet\Edurep\EckRecordsNormalizer());
        /** @var SearchResult $result */
        $result = $normalizer->normalize($response);
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EdurepRecord::class, get_class(array_shift($records)));
    }

    public function testNormalizeLomSearchResult()
    {
        $response = file_get_contents(__DIR__ . '/lomResponse.xml');
        /** @var \Kennisnet\Edurep\Normalizer $normalizer */
        $normalizer = new EdurepResponseNormalizer(new NLLOMResponseSerializer(), new NLLOMRecordNormalizer());
        /** @var SearchResult $result */
        $result = $normalizer->normalize($response);
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EdurepRecord::class, get_class(array_shift($records)));
    }
}