<?php

// based on PhpParser's builtin one
namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use PhpParser\ErrorHandler;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
class SimpleNameResolver extends NodeVisitorAbstract
{
    /** @var NameContext Naming context */
    private $nameContext;

    /** @var int|null */
    private $start_change;

    /** @var int|null */
    private $end_change;

    /**
     * Constructs a name resolution visitor.
     *
     * Options:
     *  * preserveOriginalNames (default false): An "originalName" attribute will be added to
     *    all name nodes that underwent resolution.
     *  * replaceNodes (default true): Resolved names are replaced in-place. Otherwise, a
     *    resolvedName attribute is added. (Names that cannot be statically resolved receive a
     *    namespacedName attribute, as usual.)
     *
     * @param ErrorHandler $errorHandler Error handler
     * @param array<int, array{0: int, 1: int, 2: int, 3: int}> $offset_map
     */
    public function __construct(ErrorHandler $errorHandler, ?array $offset_map = null)
    {
        if ($offset_map) {
            foreach ($offset_map as [, , $b_s, $b_e]) {
                if ($this->start_change === null) {
                    $this->start_change = $b_s;
                }

                $this->end_change = $b_e;
            }
        }

        $this->nameContext = new NameContext($errorHandler);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->nameContext->startNamespace();

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->startNamespace($node->name);
        } elseif ($node instanceof Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, null);
            }
        } elseif ($node instanceof Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, $node->prefix);
            }
        }

        if ($node instanceof Stmt\ClassMethod
            && $this->start_change
            && $this->end_change
        ) {
            /** @var array{startFilePos: int, endFilePos: int} */
            $attrs = $node->getAttributes();

            if ($cs = $node->getComments()) {
                $attrs['startFilePos'] = $cs[0]->getStartFilePos();
            }

            if ($attrs['endFilePos'] < $this->start_change
                || $attrs['startFilePos'] > $this->end_change
            ) {
                return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }
        }

        if ($node instanceof Stmt\ClassMethod
                  || $node instanceof Expr\Closure
        ) {
            $this->resolveSignature($node);
        } elseif ($node instanceof Expr\StaticCall
                  || $node instanceof Expr\StaticPropertyFetch
                  || $node instanceof Expr\ClassConstFetch
                  || $node instanceof Expr\New_
                  || $node instanceof Expr\Instanceof_
        ) {
            if ($node->class instanceof Name) {
                $node->class = $this->resolveClassName($node->class);
            }
        } elseif ($node instanceof Stmt\Catch_) {
            foreach ($node->types as &$type) {
                $type = $this->resolveClassName($type);
            }
        } elseif ($node instanceof Expr\FuncCall) {
            if ($node->name instanceof Name) {
                $node->name = $this->resolveName($node->name, Stmt\Use_::TYPE_FUNCTION);
            }
        } elseif ($node instanceof Expr\ConstFetch) {
            $node->name = $this->resolveName($node->name, Stmt\Use_::TYPE_CONSTANT);
        } elseif ($node instanceof Stmt\Trait_) {
            $this->resolveTrait($node);
        } elseif ($node instanceof Stmt\TraitUse) {
            foreach ($node->traits as &$trait) {
                $trait = $this->resolveClassName($trait);
            }

            foreach ($node->adaptations as $adaptation) {
                if (null !== $adaptation->trait) {
                    $adaptation->trait = $this->resolveClassName($adaptation->trait);
                }

                if ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                    foreach ($adaptation->insteadof as &$insteadof) {
                        $insteadof = $this->resolveClassName($insteadof);
                    }
                }
            }
        }

        return null;
    }

    private function addAlias(Stmt\UseUse $use, int $type, ?Name $prefix = null): void
    {
        // Add prefix for group uses
        /** @var Name $name */
        $name = $prefix ? Name::concat($prefix, $use->name) : $use->name;
        // Type is determined either by individual element or whole use declaration
        $type |= $use->type;

        $this->nameContext->addAlias(
            $name,
            (string) $use->getAlias(),
            $type,
            $use->getAttributes()
        );
    }

    /**
     * @param Stmt\Function_|Stmt\ClassMethod|Expr\Closure $node
     */
    private function resolveSignature(PhpParser\NodeAbstract $node): void
    {
        foreach ($node->params as $param) {
            $param->type = $this->resolveType($param->type);
        }
        $node->returnType = $this->resolveType($node->returnType);
    }

    /**
     * @param  PhpParser\Node|string|null $node
     *
     * @return null|PhpParser\Node\Identifier|PhpParser\Node\Name|PhpParser\Node\NullableType
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    private function resolveType($node): ?Node
    {
        if ($node instanceof Node\NullableType) {
            /** @psalm-suppress PossiblyInvalidPropertyAssignmentValue */
            $node->type = $this->resolveType($node->type);

            return $node;
        }
        if ($node instanceof Name) {
            return $this->resolveClassName($node);
        }

        return $node;
    }

    /**
     * Resolve name, according to name resolver options.
     *
     * @param Name $name Function or constant name to resolve
     * @param Stmt\Use_::TYPE_*  $type One of Stmt\Use_::TYPE_*
     *
     * @return Name Resolved name, or original name with attribute
     */
    protected function resolveName(Name $name, int $type): Name
    {
        $resolvedName = $this->nameContext->getResolvedName($name, $type);
        if (null !== $resolvedName) {
            $name->setAttribute('resolvedName', $resolvedName->toString());
        }

        return $name;
    }

    protected function resolveClassName(Name $name): Name
    {
        return $this->resolveName($name, Stmt\Use_::TYPE_NORMAL);
    }

    protected function resolveTrait(Stmt\Trait_ $node): void
    {
        $resolvedName = Name::concat($this->nameContext->getNamespace(), (string) $node->name);

        if (null !== $resolvedName) {
            $node->setAttribute('resolvedName', $resolvedName->toString());
        }
    }
}
