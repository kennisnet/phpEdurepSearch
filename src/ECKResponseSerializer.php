<?php
declare(strict_types=1);

namespace Kennisnet\Edurep;

use DOMDocument;

class ECKResponseSerializer
{
    public function deserialize(string $response)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($response);
        $xpath = $this->createXPath($doc);

        $records = $xpath->query('//srw:records/srw:record');

        $data = [];
        foreach ($records as $record) {
            $recordId = trim($xpath->evaluate("string(./srw:recordIdentifier/text()[1])", $record));
            /** @var \DOMNodeList $nodeList */
            $nodeList = $xpath->evaluate("./srw:recordData", $record);
            if ($nodeList && $nodeList->length) {
                $data[$recordId] = $this->serializeToArray($nodeList->item(0))['recordData'];
            }
        }

        return $data;
    }

    /**
     * @param $element \DOMElement
     * @param array $recordData
     * @param int $level
     * @return array|string
     */
    function serializeToArray($element, &$recordData = [], $level = 0)
    {
        $level++;
        //list attributes
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
//                $recordData['_attributes'][$attribute->name] = $attribute->value;

                if (isset($recordData['_attributes'][$attribute->name])) {
                    if (is_string($recordData['_attributes'][$attribute->name])) {
                        $recordData['_attributes'][$attribute->name] = [$recordData['_attributes'][$attribute->name], $attribute->value];
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
                    $this->serializeToArray($children->item($i), $recordData[$key], $level);
                }
            }
            // This is de edge of the nodes tree. Here the values will be handled
        } else if ($element->nodeType == XML_TEXT_NODE || $element->nodeType == XML_CDATA_SECTION_NODE) {
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
        if ($level == 1) {
            return $recordData;
        }
    }


    /**
     * @param DOMDocument $doc
     * @return \DOMXPath
     */
    private function createXPath(DOMDocument $doc)
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('srw', 'http://www.loc.gov/zing/srw/');
        $xpath->registerNamespace('czp', 'http://www.imsglobal.org/xsd/imsmd_v1p2');
        $xpath->registerNamespace('sad', 'http://xsd.kennisnet.nl/smd/sad');
        $xpath->registerNamespace('smo', 'http://xsd.kennisnet.nl/smd/1.0/');
        $xpath->registerNamespace('edurep', 'http://meresco.org/namespace/users/kennisnet/edurep');
        $xpath->registerNamespace('dd', 'http://meresco.org/namespace/drilldown');
        $xpath->registerNamespace('hr', 'http://xsd.kennisnet.nl/smd/hreview/1.0/');
        $xpath->registerNamespace('hr2', 'http://xsd.kennisnet.nl/smd/1.0/');

        return $xpath;
    }

}
