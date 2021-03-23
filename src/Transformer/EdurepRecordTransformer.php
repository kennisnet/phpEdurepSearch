<?php

namespace Kennisnet\Edurep\Transformer;

use Kennisnet\ECK\EckRecord;
use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\NLLOM\NLLOM;

class EdurepRecordTransformer
{
    /**
     * @return EdurepRecord[]|EckRecord[]
     * @throws \Exception
     */
    public function transform(array $records): array
    {
        $transformedRecords = [];

        foreach ($records as $record) {
            switch (get_class($record)) {
                case EdurepRecord::class:
                case EckRecord::class:
                    $eduRecord = $record; // no need to transform
                    break;
                case NLLOM::class:
                    $eduRecord = $this->fromNLLOMRecord($record);
                    break;
                default:
                    throw new \Exception(sprintf('Trying to transform from unknown class \'%s\'', get_class($record)));
            }
            $transformedRecords[$eduRecord->getRecordId()] = $eduRecord;
        }

        return $transformedRecords;
    }

    public function fromNLLOMRecord(NLLOM $nllomRecord): EdurepRecord
    {
        if (!method_exists($nllomRecord, 'getRecordId')) {
            throw new \Exception('Not implemented, NLLOM record id missing');
        }

        $edurepRecord = new EdurepRecord($nllomRecord->getRecordId());
        $edurepRecord->setTitle($nllomRecord->getTitle());
        $edurepRecord->setDescription($nllomRecord->getDescription());

        return $edurepRecord;
    }
}
