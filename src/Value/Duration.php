<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 10:07
 */
declare(strict_types=1);

namespace Kennisnet\Edurep\Value;


use phpDocumentor\Reflection\Types\Boolean;

class Duration implements Value, Nullable
{
    /**
     * @var \DateInterval
     */
    private $duration;

    /**
     * Duration constructor.
     *
     * @param \DateInterval $duration
     */
    public function __construct(\DateInterval $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @param $value \DateInterval
     * @return Duration|Value
     */
    public static function fromNative($value): self
    {
        return new self($value);
    }

    /**
     * @param Value $object
     * @return bool
     */
    public function sameValueAs(Value $object): Boolean
    {
        return $this->getValue() === $object->getValue();
    }

    /**
     * @return \DateInterval|mixed
     */
    public function getValue()
    {
        return $this->duration;
    }

    /**
     * @return string|null
     */
    public function __toString()
    {
        if ($this->duration !== null) {
            $parts    = explode(',', $this->duration->format('%d,%h,%i'));
            $parts[0] .= ' ' . ($parts[0] == 1 ? 'dag' : 'dagen');
            $parts[1] .= ' ' . ($parts[1] == 1 ? 'uur' : 'uren');
            $parts[2] .= ' ' . ($parts[2] == 1 ? 'minuut' : 'minuten');
            $parts    = array_filter(
                $parts,
                function ($part) {
                    return !preg_match('/^0\s/', $part);
                }
            );
            return implode(', ', $parts);
        }

        return null;
    }

}
