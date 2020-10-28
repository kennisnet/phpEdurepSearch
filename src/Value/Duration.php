<?php

declare(strict_types=1);

namespace Kennisnet\Edurep\Value;


use DateInterval;

class Duration implements Value, Nullable
{
    /**
     * @var DateInterval
     */
    private $duration;

    public function __construct(DateInterval $duration)
    {
        $this->duration = $duration;
    }

    public static function fromNative($value): self
    {
        return new self($value);
    }

    public function sameValueAs(Value $object): bool
    {
        return $this->getValue() === $object->getValue();
    }

    public function getValue(): DateInterval
    {
        return $this->duration;
    }

    public function __toString(): ?string
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
