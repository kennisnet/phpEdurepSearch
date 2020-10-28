<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 11-3-19
 * Time: 14:56
 */

namespace Kennisnet\Edurep\Model;


class DrilldownNavigator
{
    private $name;

    private $items;

    public function __construct(string $name, array $items)
    {
        $this->name  = $name;
        $this->items = $items;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

}