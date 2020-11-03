<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Normalizer;


use Kennisnet\Edurep\Model\EdurepRecord;

class SimpleNormalizer implements RecordNormalizer
{
    /**
     * @param array $data
     *
     * @return array<string,EdurepRecord>
     */
    public function normalize(array $data, string $schema): array
    {
        array_walk($data, function (&$record, $id) {
            $eduRecord = new EdurepRecord($id);
            $eduRecord->setTitle($record['Entry']['Title']);
            $record[$id] = $eduRecord;
        });

        return $data;
    }
}
