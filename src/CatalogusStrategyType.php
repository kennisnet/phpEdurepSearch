<?php
namespace Kennisnet\Edurep;

class CatalogusStrategyType implements StrategyType
{
    public function getSearchUrl(string $type = null): string
    {
        return "sru";
    }
}