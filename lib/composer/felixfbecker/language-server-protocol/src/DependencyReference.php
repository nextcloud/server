<?php
declare(strict_types = 1);

namespace LanguageServerProtocol;

class DependencyReference
{
    /**
     * @var mixed
     */
    public $hints;

    /**
     * @var object
     */
    public $attributes;

    /**
     * @param object $attributes
     * @param mixed  $hints
     */
    public function __construct($attributes = null, $hints = null)
    {
        $this->attributes = $attributes ?? new \stdClass;
        $this->hints = $hints;
    }
}
