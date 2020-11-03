<?php

namespace Kennisnet\Edurep\Serializer;

use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use Kennisnet\Edurep\Model\EdurepResultDocument;
use Kennisnet\Edurep\Unserializer;

/**
 * Class EdurepResponseSerializer
 *
 * @package Kennisnet\Edurep
 */
abstract class EdurepResponseUnserializer implements Unserializer
{
    const NUMBER_OF_RECORDS    = 'numberOfRecords';
    const SCHEMA               = 'schema';
    const VERSION              = 'version';
    const QUERY                = 'query';
    const START_RECORD         = 'startRecord';
    const MAXIMUM_RECORDS      = 'maximumRecords';
    const X_SCHEMAS            = 'x-schemas';
    const NEXT_RECORD_POSITION = 'nextRecordPosition';
    const RECORDS              = 'records';
    const EXTRA_RESPONSE_DATA  = 'extraResponseData';
    const DRILLDOWN            = 'drilldown';

    /**
     * @param DOMXPath                    $xpath
     * @param DOMNodeList|iterable<mixed> $records
     *
     * @return array<mixed>
     */
    abstract protected function records(DOMXPath $xpath, iterable $records): array;

    public function deserialize(string $string, ?string $format): array
    {
        $xpath    = (new EdurepResultDocument($string))->xpath;
        $response = [];
        // query the search query data from the response
        $response[self::NUMBER_OF_RECORDS] = ((int)$xpath->evaluate('string(//srw:numberOfRecords/text()[1])'));
        $response[self::SCHEMA]            = $xpath->evaluate('string(//srw:echoedSearchRetrieveRequest/srw:recordSchema/text())');
        $response[self::VERSION]           = $xpath->evaluate('string(//srw:echoedSearchRetrieveRequest/srw:version/text())');
        $response[self::QUERY]             = $xpath->evaluate('string(//srw:echoedSearchRetrieveRequest/srw:query/text())');
        $response[self::START_RECORD]      = $xpath->evaluate('string(//srw:echoedSearchRetrieveRequest/srw:startRecord/text())');
        $response[self::MAXIMUM_RECORDS]   = $xpath->evaluate('string(//srw:echoedSearchRetrieveRequest/srw:maximumRecords/text())');
        // Set the x-schema types
        $xSchemas = $xpath->evaluate('//srw:echoedSearchRetrieveRequest/srw:x-recordSchema');
        if ($xSchemas && $xSchemas->length) {
            foreach ($xSchemas as $xSchema) {
                $response[self::X_SCHEMAS][] = $this->serializeToArray($xSchema)['x-recordSchema'];
            }
        }
        $response[self::X_SCHEMAS]            = $response['x-schemas'] ?? [];
        $response[self::NEXT_RECORD_POSITION] = ((int)$xpath->evaluate('string(//srw:nextRecordPosition/text()[1])'));

        if (!$records = $xpath->query('//srw:records/srw:record')) {
            // What to do here?
            throw new Exception('No records found in Edurep response');
        }

        $response[self::RECORDS] = $this->records($xpath, $records);
        $extras                  = $xpath->query('//srw:extraResponseData');

        $response[self::DRILLDOWN] = $this->deserializeDrilldown($xpath);

        if ($extras) {
            foreach ($extras as $extra) {
                $response[self::EXTRA_RESPONSE_DATA] = $extra ? $this->serializeToArray($extra) : null;
            }
        }

        return $response;
    }

    /**
     * @param array|string|null $recordData
     */
    function serializeToArray(DOMNode $element, &$recordData = [], int $level = 0
    ): ?array {
        $level++;
        //list attributes
        if ($element->attributes !== null && is_array($recordData)) {
            foreach ($element->attributes as $attribute) {
                if (isset($recordData['_attributes'][$attribute->name])) {
                    if (is_string($recordData['_attributes'][$attribute->name])) {
                        $recordData['_attributes'][$attribute->name] = [
                            $recordData['_attributes'][$attribute->name],
                            $attribute->value
                        ];
                    } else {
                        if (is_array($recordData['_attributes'][$attribute->name])) {
                            $recordData['_attributes'][$attribute->name][] = $attribute->value;
                        } else {
                            $recordData['_attributes'][$attribute->name] = $attribute->value;
                        }
                    }
                } else {
                    $recordData['_attributes'][$attribute->name] = $attribute->value;
                }
            }
        }
        $prefix = $element->prefix;

        //handle classic node
        if ($element->nodeType == XML_ELEMENT_NODE) {
            // Remove the xml ns prefix if part of current nodeName
            $key = str_replace($prefix . ':', '', $element->nodeName);
            // Iterates recursive to all children and pass the $recordData with reference
            if ($element->hasChildNodes()) {
                $children = $element->childNodes;
                for ($i = 0; $i < $children->length; $i++) {
                    if ($children->item($i) !== null) {
                        if (!isset($recordData[$key])) {
                            $recordData[$key] = null;
                        }
                        $this->serializeToArray($children->item($i), $recordData[$key], $level);
                    }
                }
            }
            // This is de edge of the nodes tree. Here the values will be handled
        } else {
            if ($element->nodeType == XML_TEXT_NODE || $element->nodeType == XML_CDATA_SECTION_NODE) {
                // Remove empty strings including newlines and whitespaces
                $value = trim($element->nodeValue);
                if (!empty($value)) {
                    // Is array && not equal the current value as set before, convert it to an array.
                    if (is_string($recordData)) {
                        $recordData = [$recordData, $value];
                    } else {
                        if (is_array($recordData)) {
                            $recordData[] = $value;
                        } else {
                            $recordData = $value;
                        }
                    }
                }
            }
        }
        if ($level == 1) {
            return $recordData;
        }

        return null;
    }

    private function deserializeDrilldown(DOMXPath $xpath): array
    {
        $navigators = [];
        $drilldown  = $xpath->query('//srw:extraResponseData/dd:drilldown/dd:term-drilldown');
        if (!$drilldown || $drilldown->length === 0) {
            return $navigators;
        }
        $nodeList = $xpath->evaluate("./dd:navigator", $drilldown->item(0));

        foreach ($nodeList as $element) {
            $items = [];

            if (!$element->attributes) {
                continue;
            }

            $navigatorNameAttr = $element->attributes->getNamedItem('name');
            if ($navigatorNameAttr) {
                $navigatorName = $navigatorNameAttr->nodeValue;
            } else {
                continue; // do not add navigator if the name attribute is missing
            }

            foreach ($element->childNodes as $childNode) {
                $count = 0;
                if (!$childNode->attributes) {
                    continue;
                }
                $countAttr = $childNode->attributes->getNamedItem('count');
                if ($countAttr) {
                    $count = $countAttr->nodeValue;
                }
                if ($count === 0) {
                    continue; // do not add an item if the count is 0
                }
                $name    = $childNode->firstChild->data ?? 'Error in name';
                $items[] = ['name' => $name, 'count' => $count];
            }
            $navigators[] = ['name' => $navigatorName, 'items' => $items];
        }

        return $navigators;
    }
}
