<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 11-3-19
 * Time: 14:56
 */

namespace Kennisnet\Edurep\Model;


class DrilldownNavigatorItem
{
    private $name;

    private $count;

    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

}