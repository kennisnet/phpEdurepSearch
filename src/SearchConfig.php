<?php

namespace Kennisnet\Edurep;

interface SearchConfig
{
    public function getBaseUrl(): string;

    public function getStrategy(): StrategyType;

    public function getMaxCurlRetries(): int;
}
