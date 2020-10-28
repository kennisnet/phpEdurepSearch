<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 20-2-19
 * Time: 9:48
 */

namespace Kennisnet\Edurep\Serializer;


use Kennisnet\Edurep\EdurepResponseSerializer;

class DefaultResponseSerializer extends EdurepResponseSerializer
{
    protected function records(\DOMXPath $xpath, $records): array
    {
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
}
