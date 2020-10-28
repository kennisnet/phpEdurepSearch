<?php

namespace Tests\Kennisnet\Edurep;


use Kennisnet\Edurep\DefaultSearchConfig;
use Kennisnet\Edurep\EdurepSearch;
use Kennisnet\Edurep\Strategy\CatalogusStrategyType;
use Kennisnet\Edurep\Strategy\EdurepStrategyType;
use PHPUnit\Framework\TestCase;

class EdurepSearchTest extends TestCase
{

    public function testInvalidMax(): void
    {
        $this->expectExceptionMessage('The value for maximumRecords should be between 0 and 100.');

        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $edurep->setMaximumRecords(-1);
        $edurep->getRequestUrl();
    }

    public function testNoQuery(): void
    {
        $this->expectExceptionMessage('Missing query');

        $strategy = new EdurepStrategyType();
        $config   = new DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep   = new EdurepSearch($config);

        $edurep->getRequestUrl();
    }

    public function testEdurepQuery(): void
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

    public function testCatalogQuery(): void
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
}
