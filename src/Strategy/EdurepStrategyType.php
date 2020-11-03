<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Strategy;

use Kennisnet\Edurep\EdurepSearch;
use Kennisnet\Edurep\StrategyType;

class EdurepStrategyType implements StrategyType
{
    public function getSearchUrl(string $type = null): string
    {
        switch ($type) {
            case EdurepSearch::SEARCHTYPE_LOM: //LOM schema
                return "edurep/sruns";
            case EdurepSearch::SEARCHTYPE_SMO: //SMO schema
                return "smo/sruns";
            case EdurepSearch::SEARCHTYPE_PLUS: // LOM schema
                return "edurep/sruns/plus";
        }

        //Default
        return "edurep/sruns";
    }
}
