<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 19-2-19
 * Time: 12:04
 */
declare(strict_types=1);

namespace Kennisnet\Edurep\Serializer;

use Kennisnet\Edurep\EdurepResponseSerializer;
use Kennisnet\Edurep\Record;
use Kennisnet\NLLOM\DomToLomMapper;

class NLLOMResponseSerializer extends EdurepResponseSerializer
{
    protected function records(\DOMXPath $xpath, $records): array
    {
        $data = [];
        foreach ($records as $record) {
            $lom      = $xpath->query('./srw:recordData/czp:lom', $record)->item(0);
            $recordId = trim($xpath->evaluate("string(./srw:recordIdentifier/text()[1])", $record));
            //Cast DomElement to DomDocument
            $xml     = $lom->ownerDocument->saveXML($lom);
            $records = new \DOMDocument('1.0', 'utf-8');
            $records->loadXML($xml);
            $mapper          = new DomToLomMapper();
            $nllom           = $mapper->domToLom($records);
            $data[$recordId] = $nllom;
        }
        return $data;
    }

    /**
     * @param $records Record[]
     * @param $xpath \DOMXPath
     */
    function aggregateRecords($records, $xpath)
    {

    }
}
