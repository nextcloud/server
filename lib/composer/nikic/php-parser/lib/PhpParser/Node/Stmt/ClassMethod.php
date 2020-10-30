<?php declare(strict_types=1);

namespace PhpParser\Node\Stmt;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;

class ClassMethod extends Node\Stmt implements FunctionLike
{
    /** @var int Flags */
    public $flags;
    /** @var bool Whether to return by reference */
    public $byRef;
    /** @var Node\Identifier Name */
    public $name;
    /** @var Node\Param[] Parameters */
    public $params;
    /** @var null|Node\Identifier|Node\Name|Node\NullableType|Node\UnionType Return type */
    public $returnType;
    /** @var Node\Stmt[]|null Statements */
    public $stmts;
    /** @var Node\AttributeGroup[] PHP attribute groups */
    public $attrGroups;

    private static $magicNames = [
        '__construct'  => true,
        '__destruct'   => true,
        '__call'       => true,
        '__callstatic' => true,
        '__get'        => true,
        '__set'        => true,
        '__isset'      => true,
        '__unset'      => true,
        '__sleep'      => true,
        '__wakeup'     => true,
        '__tostring'   => true,
        '__set_state'  => true,
        '__clone'      => true,
        '__invoke'     => true,
        '__debuginfo'  => true,
    ];

    /**
     * Constructs a class method node.
     *
     * @param string|Node\Identifier $name Name
     * @param array $subNodes   Array of the following optional subnodes:
     *                          'flags       => MODIFIER_PUBLIC: Flags
     *                          'byRef'      => false          : Whether to return by reference
     *                          'params'     => array()        : Parameters
     *                          'returnType' => null           : Return type
     *                          'stmts'      => array()        : Statements
     *                          'attrGroups' => array()        : PHP attribute groups
     * @param array $attributes Additional attributes
     */
    public function __construct($name, array $subNodes = [], array $attributes = []) {
        $this->attributes = $attributes;
        $this->flags = $subNodes['flags'] ?? $subNodes['type'] ?? 0;
        $this->byRef = $subNodes['byRef'] ?? false;
        $this->name = \is_string($name) ? new Node\Identifier($name) : $name;
        $this->params = $subNodes['params'] ?? [];
        $returnType = $subNodes['returnType'] ?? null;
        $this->returnType = \is_string($returnType) ? new Node\Identifier($returnType) : $returnType;
        $this->stmts = array_key_exists('stmts', $subNodes) ? $subNodes['stmts'] : [];
        $this->attrGroups = $subNodes['attrGroups'] ?? [];
    }

    public function getSubNodeNames() : array {
        return ['attrGroups', 'flags', 'byRef', 'name', 'params', 'returnType', 'stmts'];
    }

    public function returnsByRef() : bool {
        return $this->byRef;
    }

    public function getParams() : array {
        return $this->params;
    }

    public function getReturnType() {
        return $this->returnType;
    }

    public function getStmts() {
        return $this->stmts;
    }

    public function getAttrGroups() : array {
        return $this->attrGroups;
    }

    /**
     * Whether the method is explicitly or implicitly public.
     *
     * @return bool
     */
    public function isPublic() : bool {
        return ($this->flags & Class_::MODIFIER_PUBLIC) !== 0
            || ($this->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
    }

    /**
     * Whether the method is protected.
     *
     * @return bool
     */
    public function isProtected() : bool {
        return (bool) ($this->flags & Class_::MODIFIER_PROTECTED);
    }

    /**
     * Whether the method is private.
     *
     * @return bool
     */
    public function isPrivate() : bool {
        return (bool) ($this->flags & Class_::MODIFIER_PRIVATE);
    }

    /**
     * Whether the method is abstract.
     *
     * @return bool
     */
    public function isAbstract() : bool {
        return (bool) ($this->flags & Class_::MODIFIER_ABSTRACT);
    }

    /**
     * Whether the method is final.
     *
     * @return bool
     */
    public function isFinal() : bool {
        return (bool) ($this->flags & Class_::MODIFIER_FINAL);
    }

    /**
     * Whether the method is static.
     *
     * @return bool
     */
    public function isStatic() : bool {
        return (bool) ($this->flags & Class_::MODIFIER_STATIC);
    }

    /**
     * Whether the method is magic.
     *
     * @return bool
     */
    public function isMagic() : bool {
        return isset(self::$magicNames[$this->name->toLowerString()]);
    }

    public function getType() : string {
        return 'Stmt_ClassMethod';
    }
}
