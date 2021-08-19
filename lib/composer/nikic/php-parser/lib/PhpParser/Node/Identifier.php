<?php declare(strict_types=1);

namespace PhpParser\Node;

use PhpParser\NodeAbstract;

/**
 * Represents a non-namespaced name. Namespaced names are represented using Name nodes.
 */
class Identifier extends NodeAbstract
{
    /** @var string Identifier as string */
    public $name;

    private static $specialClassNames = [
        'self'   => true,
        'parent' => true,
        'static' => true,
    ];

    /**
     * Constructs an identifier node.
     *
     * @param string $name       Identifier as string
     * @param array  $attributes Additional attributes
     */
    public function __construct(string $name, array $attributes = []) {
        $this->attributes = $attributes;
        $this->name = $name;
    }

    public function getSubNodeNames() : array {
        return ['name'];
    }

    /**
     * Get identifier as string.
     *
     * @return string Identifier as string.
     */
    public function toString() : string {
        return $this->name;
    }

    /**
     * Get lowercased identifier as string.
     *
     * @return string Lowercased identifier as string
     */
    public function toLowerString() : string {
        return strtolower($this->name);
    }

    /**
     * Checks whether the identifier is a special class name (self, parent or static).
     *
     * @return bool Whether identifier is a special class name
     */
    public function isSpecialClassName() : bool {
        return isset(self::$specialClassNames[strtolower($this->name)]);
    }

    /**
     * Get identifier as string.
     *
     * @return string Identifier as string
     */
    public function __toString() : string {
        return $this->name;
    }
    
    public function getType() : string {
        return 'Identifier';
    }
}
