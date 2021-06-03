<?php

namespace Kennisnet\Edurep\Normalizer;

use Kennisnet\ECK\EckRecord;
use Kennisnet\Edurep\Record as EdurepRecord;

interface RecordNormalizer
{
    /**
     * @param array  $data
     * @param string $schema
     *
     * @return array<string,EdurepRecord|EckRecord>
     */
    public function normalize(array $data, string $schema): array;
}
