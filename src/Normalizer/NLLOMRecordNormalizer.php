<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 19-2-19
 * Time: 11:49
 */
declare(strict_types=1);

namespace Kennisnet\Edurep\Normalizer;


use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\Edurep\RecordNormalizer;
use Kennisnet\NLLOM\NLLOM;

class NLLOMRecordNormalizer implements RecordNormalizer
{
    /**
     * @param $records NLLOM[]
     * @param $schema
     * @return array
     */
    public function normalize(array $records, string $schema)
    {
        $data = [];
        foreach ($records as $recordId => $record) {
            $edurepRecord = new EdurepRecord($recordId);
            $edurepRecord->setTitle(!empty($record->getTitle()) ? $record->getTitle() : 'geen titel');
            $edurepRecord->setDescription($record->getDescription() ?? '');
            $edurepRecord->setPublishDate($record->getPublishDate());
            $edurepRecord->setLocation($record->getTechnicalLocation() ?? '');
            $data[$recordId] = $edurepRecord;
        }
        return $data;
    }

}
