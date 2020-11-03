<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Strategy;

use Kennisnet\Edurep\StrategyType;

class CatalogusStrategyType implements StrategyType
{
    public function getSearchUrl(string $type = null): string
    {
        return "sru";
    }
}
