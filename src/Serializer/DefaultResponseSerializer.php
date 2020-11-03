<?php

namespace Kennisnet\Edurep\Serializer;


use DOMXPath;


class DefaultResponseSerializer extends EdurepResponseUnserializer
{
    protected function records(DOMXPath $xpath, iterable $records): array
    {
        $data = [];
        foreach ($records as $record) {
            /** @var string $recordId */
            $recordId = trim($xpath->evaluate("string(./srw:recordIdentifier/text()[1])", $record));
            $nodeList = $xpath->evaluate("./srw:recordData", $record);
            if ($nodeList && $nodeList->length) {
                $recordData = $this->serializeToArray($nodeList->item(0));
                if (is_array($recordData) && isset($recordData['recordData'])) {
                    $data[$recordId] = $recordData['recordData'];
                }
            }
        }

        return $data;
    }
}
