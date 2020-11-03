<?php

namespace Kennisnet\Edurep\Normalizer;


use Kennisnet\Edurep\Record;

interface RecordNormalizer
{
    /**
     * @param array  $data
     * @param string $schema
     *
     * @return array<string,Record>
     */
    public function normalize(array $data, string $schema): array;
}
