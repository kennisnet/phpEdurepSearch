<?php
namespace Kennisnet\Edurep;

class DefaultSearchConfig implements SearchConfig
{
    private $strategy;
    private $baseUrl;
    private $maxCurlRetries;

    public function __construct(StrategyType $strategy, $baseUrl, $maxCurlRetries = 3)
    {
        $this->strategy = $strategy;
        $this->baseUrl = $baseUrl;
        $this->maxCurlRetries = $maxCurlRetries;
    }

    public function getMaxCurlRetries(): int
    {
        return $this->maxCurlRetries;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getStrategy(): StrategyType
    {
        return $this->strategy;
    }
}
