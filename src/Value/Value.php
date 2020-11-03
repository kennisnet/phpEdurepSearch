<?php
namespace Kennisnet\Edurep\Value;


interface Value
{
    /**
     * Returns a object taking PHP native value(s) as argument(s).
     */
    /** @phpstan-ignore-next-line */
    public static function fromNative($value): Value;

    /**
     * Compare two ValueObjectInterface and tells whether they can be considered equal
     */
    public function sameValueAs(Value $object): bool;

    public function __toString(): string;

    /**
     * @return mixed
     */
    public function getValue();
}
