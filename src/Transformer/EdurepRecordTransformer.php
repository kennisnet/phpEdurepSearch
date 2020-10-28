<?php

namespace Kennisnet\Edurep\Transformer;

use Kennisnet\ECK\EckRecord;
use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\NLLOM\NLLOM;

class EdurepRecordTransformer
{
    /**
     * @param array $records
     * @return EdurepRecord[]
     * @throws \Exception
     */
    public function transform(array $records)
    {
        $transformedRecords = [];

        foreach ($records as $record) {
            switch (get_class($record)) {
                case EdurepRecord::class:
                    $eduRecord = $record; // no need to transform
                    break;
                case EckRecord::class:
                    $eduRecord = $this->fromEckRecord($record);
                    break;
                case NLLOM::class:
                    $eduRecord = $this->fromEckRecord($record);
                    break;
                default:
                    throw new \Exception('Trying to transform from unknown class');
            }
            $transformedRecords[$eduRecord->getRecordId()] = $eduRecord;
        }

        return $transformedRecords;
    }

    public function fromEckRecord(EckRecord $eckRecord): EdurepRecord
    {
        $edurepRecord = new EdurepRecord($eckRecord->getRecordId());
        $edurepRecord->setTitle($eckRecord->getTitle());
        $edurepRecord->setDescription($eckRecord->getDescription());
        $edurepRecord->setAuthors($eckRecord->getAuthors());
        $edurepRecord->setLocation($eckRecord->getLocation());
        $edurepRecord->setPublisher($eckRecord->getPublisher());

        return $edurepRecord;
    }

    public function fromNLLOMRecord(NLLOM $nllomRecord): EdurepRecord
    {
        $edurepRecord = new EdurepRecord($nllomRecord->getRecordId());
        $edurepRecord->setTitle($nllomRecord->getTitle());
        $edurepRecord->setDescription($nllomRecord->getDescription());

        return $edurepRecord;
    }
}