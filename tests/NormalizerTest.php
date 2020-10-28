<?php

use Kennisnet\ECK\EckRecordsNormalizer;
use Kennisnet\Edurep\EdurepResponseNormalizer;
use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\Edurep\Model\SearchResult;
use Kennisnet\Edurep\Normalizer\NLLOMRecordNormalizer;
use Kennisnet\Edurep\Serializer\DefaultResponseSerializer;
use Kennisnet\Edurep\Serializer\NLLOMResponseSerializer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NormalizerTest extends WebTestCase
{
    public function testNormalizeEckSearchResult()
    {
        $response = file_get_contents(__DIR__.'/eckResponse.xml');
        /** @var \Kennisnet\Edurep\Normalizer $normalizer */
        $normalizer = new EdurepResponseNormalizer(new DefaultResponseSerializer(), new EckRecordsNormalizer());
        /** @var SearchResult $result */
        $result = $normalizer->normalize($response);
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EdurepRecord::class, get_class(array_shift($records)));
    }

    public function testNormalizeLomSearchResult()
    {
        $response = file_get_contents(__DIR__.'/lomResponse.xml');
        /** @var \Kennisnet\Edurep\Normalizer $normalizer */
        $normalizer = new EdurepResponseNormalizer(new NLLOMResponseSerializer(), new NLLOMRecordNormalizer());
        /** @var SearchResult $result */
        $result = $normalizer->normalize($response);
        $this->assertNotEmpty($result->getRecords());

        $records = $result->getRecords();
        $this->assertEquals(EdurepRecord::class, get_class(array_shift($records)));
    }
}