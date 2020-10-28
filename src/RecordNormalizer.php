<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 11:11
 */

namespace Kennisnet\Edurep;


interface RecordNormalizer
{
    public function normalize(array $data, string $schema);
}
