<?php declare(strict_types=1);

namespace PhpParser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Use_;

class BuilderFactory
{
    /**
     * Creates a namespace builder.
     *
     * @param null|string|Node\Name $name Name of the namespace
     *
     * @return Builder\Namespace_ The created namespace builder
     */
    public function namespace($name) : Builder\Namespace_ {
        return new Builder\Namespace_($name);
    }

    /**
     * Creates a class builder.
     *
     * @param string $name Name of the class
     *
     * @return Builder\Class_ The created class builder
     */
    public function class(string $name) : Builder\Class_ {
        return new Builder\Class_($name);
    }

    /**
     * Creates an interface builder.
     *
     * @param string $name Name of the interface
     *
     * @return Builder\Interface_ The created interface builder
     */
    public function interface(string $name) : Builder\Interface_ {
        return new Builder\Interface_($name);
    }

    /**
     * Creates a trait builder.
     *
     * @param string $name Name of the trait
     *
     * @return Builder\Trait_ The created trait builder
     */
    public function trait(string $name) : Builder\Trait_ {
        return new Builder\Trait_($name);
    }

    /**
     * Creates a trait use builder.
     *
     * @param Node\Name|string ...$traits Trait names
     *
     * @return Builder\TraitUse The create trait use builder
     */
    public function useTrait(...$traits) : Builder\TraitUse {
        return new Builder\TraitUse(...$traits);
    }

    /**
     * Creates a trait use adaptation builder.
     *
     * @param Node\Name|string|null  $trait  Trait name
     * @param Node\Identifier|string $method Method name
     *
     * @return Builder\TraitUseAdaptation The create trait use adaptation builder
     */
    public function traitUseAdaptation($trait, $method = null) : Builder\TraitUseAdaptation {
        if ($method === null) {
            $method = $trait;
            $trait = null;
        }

        return new Builder\TraitUseAdaptation($trait, $method);
    }

    /**
     * Creates a method builder.
     *
     * @param string $name Name of the method
     *
     * @return Builder\Method The created method builder
     */
    public function method(string $name) : Builder\Method {
        return new Builder\Method($name);
    }

    /**
     * Creates a parameter builder.
     *
     * @param string $name Name of the parameter
     *
     * @return Builder\Param The created parameter builder
     */
    public function param(string $name) : Builder\Param {
        return new Builder\Param($name);
    }

    /**
     * Creates a property builder.
     *
     * @param string $name Name of the property
     *
     * @return Builder\Property The created property builder
     */
    public function property(string $name) : Builder\Property {
        return new Builder\Property($name);
    }

    /**
     * Creates a function builder.
     *
     * @param string $name Name of the function
     *
     * @return Builder\Function_ The created function builder
     */
    public function function(string $name) : Builder\Function_ {
        return new Builder\Function_($name);
    }

    /**
     * Creates a namespace/class use builder.
     *
     * @param Node\Name|string $name Name of the entity (namespace or class) to alias
     *
     * @return Builder\Use_ The created use builder
     */
    public function use($name) : Builder\Use_ {
        return new Builder\Use_($name, Use_::TYPE_NORMAL);
    }

    /**
     * Creates a function use builder.
     *
     * @param Node\Name|string $name Name of the function to alias
     *
     * @return Builder\Use_ The created use function builder
     */
    public function useFunction($name) : Builder\Use_ {
        return new Builder\Use_($name, Use_::TYPE_FUNCTION);
    }

    /**
     * Creates a constant use builder.
     *
     * @param Node\Name|string $name Name of the const to alias
     *
     * @return Builder\Use_ The created use const builder
     */
    public function useConst($name) : Builder\Use_ {
        return new Builder\Use_($name, Use_::TYPE_CONSTANT);
    }

    /**
     * Creates node a for a literal value.
     *
     * @param Expr|bool|null|int|float|string|array $value $value
     *
     * @return Expr
     */
    public function val($value) : Expr {
        return BuilderHelpers::normalizeValue($value);
    }

    /**
     * Creates variable node.
     *
     * @param string|Expr $name Name
     *
     * @return Expr\Variable
     */
    public function var($name) : Expr\Variable {
        if (!\is_string($name) && !$name instanceof Expr) {
            throw new \LogicException('Variable name must be string or Expr');
        }

        return new Expr\Variable($name);
    }

    /**
     * Normalizes an argument list.
     *
     * Creates Arg nodes for all arguments and converts literal values to expressions.
     *
     * @param array $args List of arguments to normalize
     *
     * @return Arg[]
     */
    public function args(array $args) : array {
        $normalizedArgs = [];
        foreach ($args as $arg) {
            if ($arg instanceof Arg) {
                $normalizedArgs[] = $arg;
            } else {
                $normalizedArgs[] = new Arg(BuilderHelpers::normalizeValue($arg));
            }
        }
        return $normalizedArgs;
    }

    /**
     * Creates a function call node.
     *
     * @param string|Name|Expr $name Function name
     * @param array            $args Function arguments
     *
     * @return Expr\FuncCall
     */
    public function funcCall($name, array $args = []) : Expr\FuncCall {
        return new Expr\FuncCall(
            BuilderHelpers::normalizeNameOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates a method call node.
     *
     * @param Expr                   $var  Variable the method is called on
     * @param string|Identifier|Expr $name Method name
     * @param array                  $args Method arguments
     *
     * @return Expr\MethodCall
     */
    public function methodCall(Expr $var, $name, array $args = []) : Expr\MethodCall {
        return new Expr\MethodCall(
            $var,
            BuilderHelpers::normalizeIdentifierOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates a static method call node.
     *
     * @param string|Name|Expr       $class Class name
     * @param string|Identifier|Expr $name  Method name
     * @param array                  $args  Method arguments
     *
     * @return Expr\StaticCall
     */
    public function staticCall($class, $name, array $args = []) : Expr\StaticCall {
        return new Expr\StaticCall(
            BuilderHelpers::normalizeNameOrExpr($class),
            BuilderHelpers::normalizeIdentifierOrExpr($name),
            $this->args($args)
        );
    }

    /**
     * Creates an object creation node.
     *
     * @param string|Name|Expr $class Class name
     * @param array            $args  Constructor arguments
     *
     * @return Expr\New_
     */
    public function new($class, array $args = []) : Expr\New_ {
        return new Expr\New_(
            BuilderHelpers::normalizeNameOrExpr($class),
            $this->args($args)
        );
    }

    /**
     * Creates a constant fetch node.
     *
     * @param string|Name $name Constant name
     *
     * @return Expr\ConstFetch
     */
    public function constFetch($name) : Expr\ConstFetch {
        return new Expr\ConstFetch(BuilderHelpers::normalizeName($name));
    }
    
    /**
     * Creates a property fetch node.
     *
     * @param Expr                   $var  Variable holding object
     * @param string|Identifier|Expr $name Property name
     *
     * @return Expr\PropertyFetch
     */
    public function propertyFetch(Expr $var, $name) : Expr\PropertyFetch {
        return new Expr\PropertyFetch($var, BuilderHelpers::normalizeIdentifierOrExpr($name));
    }

    /**
     * Creates a class constant fetch node.
     *
     * @param string|Name|Expr  $class Class name
     * @param string|Identifier $name  Constant name
     *
     * @return Expr\ClassConstFetch
     */
    public function classConstFetch($class, $name): Expr\ClassConstFetch {
        return new Expr\ClassConstFetch(
            BuilderHelpers::normalizeNameOrExpr($class),
            BuilderHelpers::normalizeIdentifier($name)
        );
    }

    /**
     * Creates nested Concat nodes from a list of expressions.
     *
     * @param Expr|string ...$exprs Expressions or literal strings
     *
     * @return Concat
     */
    public function concat(...$exprs) : Concat {
        $numExprs = count($exprs);
        if ($numExprs < 2) {
            throw new \LogicException('Expected at least two expressions');
        }

        $lastConcat = $this->normalizeStringExpr($exprs[0]);
        for ($i = 1; $i < $numExprs; $i++) {
            $lastConcat = new Concat($lastConcat, $this->normalizeStringExpr($exprs[$i]));
        }
        return $lastConcat;
    }

    /**
     * @param string|Expr $expr
     * @return Expr
     */
    private function normalizeStringExpr($expr) : Expr {
        if ($expr instanceof Expr) {
            return $expr;
        }

        if (\is_string($expr)) {
            return new String_($expr);
        }

        throw new \LogicException('Expected string or Expr');
    }
}
