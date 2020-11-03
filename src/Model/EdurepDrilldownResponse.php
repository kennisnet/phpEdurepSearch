<?php

declare(strict_types=1);

namespace Kennisnet\Edurep\Model;


class EdurepDrilldownResponse
{
    /**
     * @var DrilldownNavigator[]
     */
    private $navigators;

    public function __construct(array $navigators = [])
    {
        $this->navigators = $navigators;
    }

    public function getNavigators(): array
    {
        return $this->navigators;
    }
}
