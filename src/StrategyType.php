<?php

namespace Kennisnet\Edurep;

interface StrategyType
{
    public function getSearchUrl(string $type = null): string;
}
