<?php

namespace Kennisnet\Edurep;

class EdurepStrategyType implements StrategyType
{
    public function getSearchUrl(string $type = null): string
    {
        switch ($type) {
            case EdurepSearch::SEARCHTYPE_LOM:
                return "edurep/sruns";
            case EdurepSearch::SEARCHTYPE_SMO:
                return "smo/sruns";
            case EdurepSearch::SEARCHTYPE_PLUS:
                return "edurep/sruns/plus";
        }

        //Default
        return "edurep/sruns";
    }
}