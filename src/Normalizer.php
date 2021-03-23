<?php

namespace Kennisnet\Edurep;

/** @deprecated use Kennisnet\Edurep\Normalizer\RecordNormalizer instead */
interface Normalizer
{
    /**
     * @return mixed
     */
    public function normalize(array $data, string $schema);
}
