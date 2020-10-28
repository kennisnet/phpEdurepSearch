<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 15-2-19
 * Time: 13:30
 */

namespace Kennisnet\Edurep;


interface Serializer
{
    /**
     * @param $string
     * @return array
     */
    public function deserialize($string): array;

}
