<?php declare(strict_types=1);

namespace PhpParser\Builder;

use PhpParser;
use PhpParser\BuilderHelpers;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt;

class Property implements PhpParser\Builder
{
    protected $name;

    protected $flags = 0;
    protected $default = null;
    protected $attributes = [];

    /** @var null|Identifier|Name|NullableType */
    protected $type;

    /**
     * Creates a property builder.
     *
     * @param string $name Name of the property
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Makes the property public.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function makePublic() {
        $this->flags = BuilderHelpers::addModifier($this->flags, Stmt\Class_::MODIFIER_PUBLIC);

        return $this;
    }

    /**
     * Makes the property protected.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function makeProtected() {
        $this->flags = BuilderHelpers::addModifier($this->flags, Stmt\Class_::MODIFIER_PROTECTED);

        return $this;
    }

    /**
     * Makes the property private.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function makePrivate() {
        $this->flags = BuilderHelpers::addModifier($this->flags, Stmt\Class_::MODIFIER_PRIVATE);

        return $this;
    }

    /**
     * Makes the property static.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function makeStatic() {
        $this->flags = BuilderHelpers::addModifier($this->flags, Stmt\Class_::MODIFIER_STATIC);

        return $this;
    }

    /**
     * Sets default value for the property.
     *
     * @param mixed $value Default value to use
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function setDefault($value) {
        $this->default = BuilderHelpers::normalizeValue($value);

        return $this;
    }

    /**
     * Sets doc comment for the property.
     *
     * @param PhpParser\Comment\Doc|string $docComment Doc comment to set
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function setDocComment($docComment) {
        $this->attributes = [
            'comments' => [BuilderHelpers::normalizeDocComment($docComment)]
        ];

        return $this;
    }

    /**
     * Sets the property type for PHP 7.4+.
     *
     * @param string|Name|NullableType|Identifier $type
     *
     * @return $this
     */
    public function setType($type) {
        $this->type = BuilderHelpers::normalizeType($type);

        return $this;
    }

    /**
     * Returns the built class node.
     *
     * @return Stmt\Property The built property node
     */
    public function getNode() : PhpParser\Node {
        return new Stmt\Property(
            $this->flags !== 0 ? $this->flags : Stmt\Class_::MODIFIER_PUBLIC,
            [
                new Stmt\PropertyProperty($this->name, $this->default)
            ],
            $this->attributes,
            $this->type
        );
    }
}
