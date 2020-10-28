<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 19-2-19
 * Time: 11:36
 */

namespace Kennisnet\Edurep;


use Kennisnet\Edurep\Model\EdurepRecord;

class SimpleNormalizer implements RecordNormalizer
{
    /**
     * @param $data array
     *
     * @return array
     */
    public function normalize(array $data, string $schema)
    {
        array_walk($data, function (&$record, $id) {
            $eduRecord = new EdurepRecord($id);
            $eduRecord->setTitle($record['Entry']['Title']);
            $record = $eduRecord;
        });

        return $data;
    }
}
