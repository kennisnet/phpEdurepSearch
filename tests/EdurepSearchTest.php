<?php

namespace Tests\Kennisnet\Edurep;

use PHPUnit\Framework\TestCase;

class EdurepSearchTest extends TestCase
{

    public function testInvalidMax()
    {
        $this->expectExceptionMessage('The value for maximumRecords should be between 0 and 100.');

        $strategy = new \Kennisnet\Edurep\EdurepStrategyType();
        $config = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep = new \Kennisnet\Edurep\EdurepSearch($config);
        $edurep->setMaximumRecords(-1);
        $edurep->getRequestUrl();
    }

    public function testNoQuery()
    {
        $this->expectExceptionMessage('Missing query');

        $strategy = new \Kennisnet\Edurep\EdurepStrategyType();
        $config = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "http://wszoeken.edurep.kennisnet.nl:8000/");
        $edurep = new \Kennisnet\Edurep\EdurepSearch($config);

        $edurep->getRequestUrl();
    }

    public function testEdurepQuery()
    {
        $this->expectExceptionMessage('Could not resolve host: dummy');

        $strategy = new \Kennisnet\Edurep\EdurepStrategyType();
        $config = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "dummy/");
        $edurep = new \Kennisnet\Edurep\EdurepSearch($config);
        $edurep
            ->setQuery("math")
            ->setRecordSchema("oai_dc")
            ->setMaximumRecords(7)
            ->setStartRecord(3)
            ->setXtermDrilldown("lom.technical.format:5,lom.rights.cost:2")
            ->addXRecordSchema("smbAggregatedData")
            ->addXRecordSchema("extra")
            ->setSortKeys('test')
        ;

        $this->assertEquals([
            'operation' => 'searchRetrieve',
            'version' => '1.2',
            'recordPacking' => 'xml',
            'query' => 'math',
            'recordSchema' => 'oai_dc',
            'maximumRecords' => 7,
            'startRecord' => 3,
            'sortKeys' => 'test',
            'x-term-drilldown' => 'lom.technical.format:5,lom.rights.cost:2'
        ], $edurep->getParameters());

        $this->assertEquals('dummy/edurep/sruns?operation=searchRetrieve&version=1.2&recordPacking=xml&query=math&maximumRecords=7&recordSchema=oai_dc&startRecord=3&x-term-drilldown=lom.technical.format:5,lom.rights.cost:2&sortKeys=test&x-recordSchema=smbAggregatedData&x-recordSchema=extra',
            $edurep->getRequestUrl()
        );

    }

    public function testCatalogQuery()
    {
        $strategy = new \Kennisnet\Edurep\CatalogusStrategyType();
        $config = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "https://staging.catalogusservice.edurep.nl/");
        $edurep = new \Kennisnet\Edurep\EdurepSearch($config);
        $edurep
            ->setQuery("math")
        ;

        $this->assertEquals([
            'operation' => 'searchRetrieve',
            'version' => '1.2',
            'recordPacking' => 'xml',
            'query' => 'math',
            'maximumRecords' => 100
        ], $edurep->getParameters());

        $this->assertEquals('https://staging.catalogusservice.edurep.nl/sru?operation=searchRetrieve&version=1.2&recordPacking=xml&query=math&maximumRecords=100',
            $edurep->getRequestUrl()
        );
    }
}