<?php

namespace Tests\Kennisnet\Edurep;

use Kennisnet\Edurep\DefaultSearchConfig;
use Kennisnet\Edurep\EdurepSearch;
use Kennisnet\Edurep\SearchClient;
use Kennisnet\Edurep\Strategy\CatalogusStrategyType;
use Kennisnet\Edurep\Strategy\EdurepStrategyType;
use PHPUnit\Framework\TestCase;

class EdurepSearchTest extends TestCase
{

    public function test_exception_thrown_on_setting_invalid_maximum_records_value(): void
    {
        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $this->expectExceptionMessage('The value for maximumRecords should be between 0 and 100.');

        $edurep->setMaximumRecords(-1);
    }

    public function test_exception_thrown_on_missing_query_when_getting_request_url(): void
    {
        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $this->expectExceptionMessage('Missing query');

        $edurep->getRequestUrl();
    }

    public function test_catalogus_can_set_infinitely_high_start_record(): void
    {
        $strategy = new CatalogusStrategyType();
        $config = new DefaultSearchConfig($strategy, "https://staging.catalogusservice.edurep.nl/");
        $edurep = new EdurepSearch($config);

        $edurep->setStartRecord(3000);
        $edurep->setQuery("mbo");

        $this->assertEquals(
            'https://staging.catalogusservice.edurep.nl/sru?operation=searchRetrieve&version=1.2&recordPacking=xml&query=mbo&maximumRecords=100&startRecord=3000',
            $edurep->getRequestUrl(),
            "Test catalogus URL with startRecord 3000"
        );

        $this->assertEquals(3000, $edurep->getParameters()['startRecord'], "Test catalogus can use unrestrained start record");
    }

    public function test_catalogus_can_set_infinitely_high_maximum_records(): void
    {
        $strategy = new CatalogusStrategyType();
        $config   = new DefaultSearchConfig($strategy, "https://staging.catalogusservice.edurep.nl/");
        $edurep   = new EdurepSearch($config);

        $edurep->setMaximumRecords(1000);
        $edurep->setQuery("mbo");

        $this->assertEquals(
            'https://staging.catalogusservice.edurep.nl/sru?operation=searchRetrieve&version=1.2&recordPacking=xml&query=mbo&maximumRecords=1000',
            $edurep->getRequestUrl(),
            "Test catalogus URL with maximumRecords 1000"
        );

        $this->assertEquals(1000, $edurep->getParameters()['maximumRecords'], "Test catalogus can use unrestrained maxiumum records");
    }

    public function test_edurep_cant_use_unrestrained_maximum_records(): void
    {
        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $this->expectExceptionMessage('The value for maximumRecords should be between 0 and 100.');

        $edurep->setMaximumRecords(1000);
    }

    public function test_edurep_cant_use_unrestrained_start_record(): void
    {
        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $this->expectExceptionMessage('The value for startRecords should be between 1 and 1000.');

        $edurep->setStartRecord(3000);
    }

    public function test_edurep_query(): void
    {
        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $edurep->setQuery("math")
               ->setRecordSchema("oai_dc")
               ->setMaximumRecords(7)
               ->setStartRecord(3)
               ->setXtermDrilldown("lom.technical.format:5,lom.rights.cost:2")
               ->addXRecordSchema("smbAggregatedData")
               ->addXRecordSchema("extra")
               ->setSortKeys('test');

        $this->assertEquals(
            [
                'operation'        => 'searchRetrieve',
                'version'          => '1.2',
                'recordPacking'    => 'xml',
                'query'            => '',
                'recordSchema'     => 'oai_dc',
                'maximumRecords'   => 7,
                'startRecord'      => 3,
                'sortKeys'         => 'test',
                'x-term-drilldown' => 'lom.technical.format:5,lom.rights.cost:2',
                'x-recordSchemas'  => [
                    'smbAggregatedData',
                    'extra'
                ]
            ],
            $edurep->getParameters()
        );

        $this->assertEquals(
            'http://wszoeken.edurep.kennisnet.nl:8000/edurep/sruns?operation=searchRetrieve&version=1.2&recordPacking=xml&query=math&maximumRecords=7&recordSchema=oai_dc&startRecord=3&x-term-drilldown=lom.technical.format:5,lom.rights.cost:2&sortKeys=test&x-recordSchema=smbAggregatedData&x-recordSchema=extra',
            $edurep->getRequestUrl()
        );
    }

    public function test_catalogus_query(): void
    {
        $strategy = new CatalogusStrategyType();
        $config   = new DefaultSearchConfig($strategy, "https://staging.catalogusservice.edurep.nl/");
        $edurep   = new EdurepSearch($config);

        $edurep->setQuery("math");

        $this->assertEquals(
            [
                'operation'      => 'searchRetrieve',
                'version'        => '1.2',
                'recordPacking'  => 'xml',
                'query'          => '',
                'maximumRecords' => 100
            ],
            $edurep->getParameters()
        );

        $this->assertEquals(
            'https://staging.catalogusservice.edurep.nl/sru?operation=searchRetrieve&version=1.2&recordPacking=xml&query=math&maximumRecords=100',
            $edurep->getRequestUrl()
        );
    }

    public function test_default_search()
    {
        $strategy     = new EdurepStrategyType();
        $config       = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $searchClient = new class() extends SearchClient {
            public function executeQuery(string $request, int $maxRetries): string
            {
                return file_get_contents(__DIR__ . '/default.xml');
            }
        };
        $edurep       = new EdurepSearch($config, $searchClient);

        $edurep->setQuery("math")
               ->setRecordSchema("oai_dc")
               ->addXRecordSchema("smbAggregatedData")
               ->addXRecordSchema("extra")
               ->setSortKeys('test');
        $result = $edurep->search();

        $this->assertEquals($searchClient->executeQuery('', 10), $result);
    }
}
