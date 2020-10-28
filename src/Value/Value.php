<?php
namespace Kennisnet\Edurep\Value;


interface Value
{
    /**
     * Returns a object taking PHP native value(s) as argument(s).
     *
     * @param $value
     *
     * @return Value
     */
    public static function fromNative($value);

    /**
     * Compare two ValueObjectInterface and tells whether they can be considered equal
     *
     * @param Value $object
     *
     * @return bool
     */
    public function sameValueAs(Value $object);

    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function __toString();

    /**
     * @return mixed
     */
    public function getValue();
}
