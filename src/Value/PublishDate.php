<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 18-2-19
 * Time: 9:33
 */

namespace Kennisnet\Edurep\Value;


class PublishDate implements Value, Nullable
{
    /**
     * @var \DateTime| null
     */
    private $publishDate = null;

    public function __construct(\DateTime $dateTime = null)
    {
        $this->publishDate = $dateTime;
    }

    /**
     * @param \DateTime $value
     * @return PublishDate
     */
    public static function fromNative($value)
    {
        return new self($value);
    }

    public function sameValueAs(Value $object)
    {
        return $this->getValue() === $object->getValue();
    }

    /**
     * @return \DateTime|null
     */
    public function getValue()
    {
        return $this->publishDate;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function __toString(): string
    {
        if (!$this->publishDate) return null;

        $now  = new \DateTime();
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
