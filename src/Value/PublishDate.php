<?php

namespace Kennisnet\Edurep\Value;


use DateTime;

class PublishDate implements Value, Nullable
{
    /**
     * @var DateTime|null
     */
    private $publishDate = null;

    public function __construct(DateTime $dateTime = null)
    {
        $this->publishDate = $dateTime;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public static function fromNative($value): Value
    {
        return new self($value);
    }

    public function sameValueAs(Value $object): bool
    {
        return $this->getValue() === $object->getValue();
    }

    public function getValue(): ?DateTime
    {
        return $this->publishDate;
    }

    public function __toString(): string
    {
        if (!$this->publishDate) {
            return '';
        }

        $now  = new DateTime();
        $diff = $this->publishDate->diff($now);

        $parts    = explode(',', $diff->format('%y,%m,%d'));
        $parts[0] .= ' jaar geleden';
        $parts[1] .= ' ' . ($parts[1] == 1 ? 'maand geleden' : 'maanden geleden');
        $parts[2] .= ' ' . ($parts[2] == 1 ? 'dag geleden' : 'dagen geleden');
        $parts    = array_filter(
            $parts,
            function ($part) {
                return !preg_match('/^0\s/', $part);
            }
        );

        if (count($parts) > 0) {
            return reset($parts);
        } else {
            return 'vandaag';
        }
    }

}
