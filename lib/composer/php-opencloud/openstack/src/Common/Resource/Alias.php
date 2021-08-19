<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

class Alias
{
    /**
     * @var string
     */
    public $propertyName;

    /**
     * @var bool
     */
    private $isList;

    /**
     * @var string
     */
    private $className;

    /**
     * @param string      $propertyName A name of the property in target resource class
     * @param string|null $className    A class name for the property value
     * @param bool        $list         Whether value of the property should be treated as a list or not
     */
    public function __construct(string $propertyName, string $className = null, bool $list = false)
    {
        $this->isList       = $list;
        $this->propertyName = $propertyName;
        $this->className    = $className && class_exists($className) ? $className : null;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function getValue(ResourceInterface $resource, $value)
    {
        if (null === $value || !$this->className) {
            return $value;
        } elseif ($this->isList && is_array($value)) {
            $array = [];
            foreach ($value as $subVal) {
                $array[] = $resource->model($this->className, $subVal);
            }

            return $array;
        } elseif (\DateTimeImmutable::class === $this->className) {
            return new \DateTimeImmutable($value);
        }

        return $resource->model($this->className, $value);
    }
}
