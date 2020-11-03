<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Serializer;

use DOMDocument;
use DOMXPath;
use Kennisnet\NLLOM\DomToLomMapper;
use Kennisnet\NLLOM\NLLOM;


class NLLOMResponseSerializer extends EdurepResponseUnserializer
{
    /**
     * @return array<string,NLLOM>|array
     */
    protected function records(DOMXPath $xpath, iterable $records): array
    {
        $data = [];
        foreach ($records as $record) {
            if (!$node = $xpath->query('./srw:recordData/czp:lom', $record)) {
                continue;
            }

            $lom      = $node->item(0);
            $recordId = trim($xpath->evaluate("string(./srw:recordIdentifier/text()[1])", $record));

            if (!$xml = $lom->ownerDocument->saveXML($lom)) {
                continue;
            }

            $records = new DOMDocument('1.0', 'utf-8');
            $records->loadXML($xml);

            $mapper = new DomToLomMapper();
            $nllom  = $mapper->domToLom($records);

            $data[$recordId] = $nllom;
        }

        return $data;
    }
}
