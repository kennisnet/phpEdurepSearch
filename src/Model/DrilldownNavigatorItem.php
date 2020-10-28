<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Model;


class DrilldownNavigatorItem
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $count;

    public function __construct(string $name, int $count)
    {
        $this->name  = $name;
        $this->count = $count;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCount(): int
    {
        return $this->count;
    }

}