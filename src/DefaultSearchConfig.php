<?php
declare(strict_types=1);

namespace Kennisnet\Edurep;

class DefaultSearchConfig implements SearchConfig
{
    /**
     * @var StrategyType
     */
    private $strategy;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $maxCurlRetries;

    public function __construct(StrategyType $strategy, string $baseUrl, int $maxCurlRetries = 3)
    {
        $this->strategy       = $strategy;
        $this->baseUrl        = $baseUrl;
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
