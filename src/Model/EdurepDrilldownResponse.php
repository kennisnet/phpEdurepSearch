<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 9:24
 */
declare(strict_types=1);

namespace Kennisnet\Edurep\Model;


class EdurepDrilldownResponse
{
    private $navigators;

    public function __construct(array $navigators = [])
    {
        $this->navigators = $navigators;
    }

    /**
     * @return mixed
     */
    public function getNavigators()
    {
        return $this->navigators;
    }
}
