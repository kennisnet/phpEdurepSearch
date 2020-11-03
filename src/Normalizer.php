<?php

namespace Kennisnet\Edurep;


interface Normalizer
{
    /**
     * @return mixed
     */
    public function normalize(array $data, string $schema);
}
