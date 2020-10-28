<?php
declare(strict_types=1);

namespace Kennisnet\Edurep;

class EckRecordsNormalizer implements RecordNormalizer
{
    /**
     * @param $data
     * @param $schema
     * @return EckRecord[]
     */
    public function normalize(array $recordData, string $schema): array
    {
        switch ($schema) {
            case EckRecordSchemaTypes::ECKCS_2_3:
            case EckRecordSchemaTypes::ECKCS_2_2:
            case EckRecordSchemaTypes::ECKCS_2_1_1:
                return $this->normalizeECKCS($recordData);
            default:
                return [];
        }
    }

    /**
     * universal normalizer
     *
     * @param $data
     * @return array
     */
    private function normalizeECKCS($data)
    {
        $records = [];

        foreach ($data as $recordId => $record) {
            $recordData = $record['Entry'];

            $eckRecord = new EckRecord($recordId, $recordData[EckRecord::TITLE]);
            $eckRecord->setDescription($recordData[EckRecord::DESCRIPTION] ?? '');
            $eckRecord->setLocation($recordData[EckRecord::LOCATION] ?? '');
            if (isset($recordData[EckRecord::PUBLISHER]))
                $eckRecord->setPublisher($recordData[EckRecord::PUBLISHER]);

            if (isset($recordData[EckRecord::AUTHORS])) {
                foreach ($recordData[EckRecord::AUTHORS] as $author) {
                    /* Confusing naming. Author can be plural */
                    if (is_string($author)) {
                        $eckRecord->addAuthor($author);
                    }
                    if (is_array($author)) {
                        foreach ($author as $singleAuthor) {
                            $eckRecord->addAuthor($singleAuthor);
                        }
                    }
                }
            }

            $records[$eckRecord->getRecordId()] = $eckRecord;
        }

        return $records;
    }

    /**
     * Helper function to extract an array of records in case you want to use the phpECKCS library standalone
     *
     * @param string $responseString
     * @return array
     */
    public function deserializeFromSearchResponse(string $responseString)
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->loadXML($responseString);
        $xpath = $this->createXPath($doc);

        // query the search query data from the response
        /** @var \DOMNodeList $records */
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
     * @param \DOMDocument $doc
     * @return \DOMXPath
     */
    private function createXPath(\DOMDocument $doc)
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

    /**
     * @var \DOMElement $element
     * @return array|string
     */
    private function serializeToArray($element, &$recordData = [], $level = 0)
    {
        $level++;
        //list attributes
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
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
}