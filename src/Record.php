<?php

namespace Kennisnet\Edurep;

interface Record
{
    public function getRecordId(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    public function getLocation(): string;
}
