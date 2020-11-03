<?php
namespace Kennisnet\Edurep\Model;


class DrilldownNavigator
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var DrilldownNavigatorItem[]
     */
    private $items;

    public function __construct(string $name, array $items)
    {
        $this->name  = $name;
        $this->items = $items;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getItems(): array
    {
        return $this->items;
    }

}