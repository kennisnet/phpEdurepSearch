<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Normalizer;


use Kennisnet\Edurep\Model\EdurepRecord;
use Kennisnet\NLLOM\NLLOM;

class NLLOMRecordNormalizer implements RecordNormalizer
{
    /**
     * @param  array<string,NLLOM> $records
     * @return array<string,EdurepRecord>|array
     */
    public function normalize(array $records, string $schema): array
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
