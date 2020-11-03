<?php
namespace Kennisnet\Edurep;


interface Unserializer
{
    /**
     * @return array<mixed>
     */
    public function deserialize(string $string, string $format): array;

}
