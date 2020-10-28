<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 10:59
 */

namespace Kennisnet\Edurep;


interface RecordReader
{
    public function getRecords(): array;

    public function readRecords($records): self;

    public function getSchema(): string;
}
