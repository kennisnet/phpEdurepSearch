<?php

namespace Tests\Kennisnet\Edurep;


use Kennisnet\Edurep\Serializer\DefaultResponseSerializer;
use PHPUnit\Framework\TestCase;

class DefaultResponseSerializerTest extends TestCase
{
    public function testDeserializeValidDrilldown()
    {
        $validResponseXml
            = '<?xml version="1.0" encoding="UTF-8"?><srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/" xmlns:xcql="http://www.loc.gov/zing/cql/xcql/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meresco_srw="http://meresco.org/namespace/srw#"><srw:version>1.2</srw:version><srw:numberOfRecords>325</srw:numberOfRecords><srw:echoedSearchRetrieveRequest><srw:version>1.2</srw:version><srw:query>test</srw:query><srw:startRecord>1</srw:startRecord><srw:maximumRecords>0</srw:maximumRecords><srw:recordPacking>xml</srw:recordPacking><srw:recordSchema>eckcs2.2</srw:recordSchema><srw:extraRequestData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown>meta.repository.id:0,Medium:0</dd:term-drilldown></dd:drilldown></srw:extraRequestData></srw:echoedSearchRetrieveRequest><srw:extraResponseData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown><dd:navigator name="meta.repository.id"><dd:item count="325">deviant_test</dd:item></dd:navigator><dd:navigator name="Medium"><dd:item count="188">Boek</dd:item><dd:item count="85">Web browser</dd:item><dd:item count="52">Anders</dd:item></dd:navigator></dd:term-drilldown></dd:drilldown>
        <querytimes xmlns="http://meresco.org/namespace/timing">
            <sruHandling>PT0.006S</sruHandling>
            <sruQueryTime>PT0.005S</sruQueryTime>
            <index>PT0.001S</index>
        </querytimes>
    </srw:extraResponseData></srw:searchRetrieveResponse>';

        $serializer = new DefaultResponseSerializer();

        $response = $serializer->deserialize($validResponseXml);

        $this->assertEquals(2, count($response['drilldown']));
        $this->assertEquals('meta.repository.id', $response['drilldown'][0]['name']);
        $this->assertEquals('Medium', $response['drilldown'][1]['name']);

    }

    public function testDeserializeInvalidCountAttrNameInDrilldown()
    {
        /* The drilldown in this response contains invalid count attribute (Navigator 'aantal' where 'count' is expected) */
        $invalidResponseXml
            = '<?xml version="1.0" encoding="UTF-8"?><srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/" xmlns:xcql="http://www.loc.gov/zing/cql/xcql/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meresco_srw="http://meresco.org/namespace/srw#"><srw:version>1.2</srw:version><srw:numberOfRecords>325</srw:numberOfRecords><srw:echoedSearchRetrieveRequest><srw:version>1.2</srw:version><srw:query>test</srw:query><srw:startRecord>1</srw:startRecord><srw:maximumRecords>0</srw:maximumRecords><srw:recordPacking>xml</srw:recordPacking><srw:recordSchema>eckcs2.2</srw:recordSchema><srw:extraRequestData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown>meta.repository.id:0,Medium:0</dd:term-drilldown></dd:drilldown></srw:extraRequestData></srw:echoedSearchRetrieveRequest><srw:extraResponseData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown><dd:navigator name="meta.repository.id"><dd:item aantal="325">deviant_test</dd:item></dd:navigator><dd:navigator name="Medium"><dd:item count="188">Boek</dd:item><dd:item count="85">Web browser</dd:item><dd:item count="52">Anders</dd:item></dd:navigator></dd:term-drilldown></dd:drilldown>
        <querytimes xmlns="http://meresco.org/namespace/timing">
            <sruHandling>PT0.006S</sruHandling>
            <sruQueryTime>PT0.005S</sruQueryTime>
            <index>PT0.001S</index>
        </querytimes>
    </srw:extraResponseData></srw:searchRetrieveResponse>';

        $serializer = new DefaultResponseSerializer();

        $response = $serializer->deserialize($invalidResponseXml);

        $this->assertEquals(2, count($response['drilldown']));
        $this->assertEquals(0, count($response['drilldown'][0]['items']));
        $this->assertEquals(3, count($response['drilldown'][1]['items']));
        $this->assertEquals(188, $response['drilldown'][1]['items'][0]['count']);
    }

    public function testDeserializeInvalidTitleAttrNameInDrilldown()
    {
        /* The drilldown in this response contains invalid count attribute (Navigator has 'title' attribute instead of 'name') */
        $invalidResponseXml
            = '<?xml version="1.0" encoding="UTF-8"?><srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/" xmlns:xcql="http://www.loc.gov/zing/cql/xcql/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meresco_srw="http://meresco.org/namespace/srw#"><srw:version>1.2</srw:version><srw:numberOfRecords>325</srw:numberOfRecords><srw:echoedSearchRetrieveRequest><srw:version>1.2</srw:version><srw:query>test</srw:query><srw:startRecord>1</srw:startRecord><srw:maximumRecords>0</srw:maximumRecords><srw:recordPacking>xml</srw:recordPacking><srw:recordSchema>eckcs2.2</srw:recordSchema><srw:extraRequestData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown>meta.repository.id:0,Medium:0</dd:term-drilldown></dd:drilldown></srw:extraRequestData></srw:echoedSearchRetrieveRequest><srw:extraResponseData><dd:drilldown
    xmlns:dd="http://meresco.org/namespace/drilldown"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://meresco.org/namespace/drilldown http://meresco.org/files/xsd/drilldown-20070730.xsd"><dd:term-drilldown><dd:navigator title="meta.repository.id"><dd:item aantal="325">deviant_test</dd:item></dd:navigator><dd:navigator name="Medium"><dd:item count="188">Boek</dd:item><dd:item count="85">Web browser</dd:item><dd:item count="52">Anders</dd:item></dd:navigator></dd:term-drilldown></dd:drilldown>
        <querytimes xmlns="http://meresco.org/namespace/timing">
            <sruHandling>PT0.006S</sruHandling>
            <sruQueryTime>PT0.005S</sruQueryTime>
            <index>PT0.001S</index>
        </querytimes>
    </srw:extraResponseData></srw:searchRetrieveResponse>';

        $serializer = new DefaultResponseSerializer();

        $response = $serializer->deserialize($invalidResponseXml);

        $this->assertEquals(1, count($response['drilldown']));
    }
}
