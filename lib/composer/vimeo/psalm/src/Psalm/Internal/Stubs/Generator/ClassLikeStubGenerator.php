<?php

namespace Psalm\Internal\Stubs\Generator;

use PhpParser;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Type;

class ClassLikeStubGenerator
{
    /**
     * @return PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_
     */
    public static function getClassLikeNode(
        \Psalm\Codebase $codebase,
        ClassLikeStorage $storage,
        string $classlike_name
    ) : PhpParser\Node\Stmt\ClassLike {
        $subnodes = [
            'stmts' => array_merge(
                self::getConstantNodes($codebase, $storage),
                self::getPropertyNodes($storage),
                self::getMethodNodes($storage)
            )
        ];

        $docblock = new ParsedDocblock('', []);

        $template_offset = 0;

        foreach ($storage->template_types ?: [] as $template_name => $map) {
            $type = array_values($map)[0][0];

            $key = isset($storage->template_covariants[$template_offset]) ? 'template-covariant' : 'template';

            $docblock->tags[$key][] = $template_name . ' as ' . $type->toNamespacedString(
                null,
                [],
                null,
                false
            );

            $template_offset++;
        }

        $attrs = [
            'comments' => $docblock->tags
                ? [
                    new PhpParser\Comment\Doc(
                        \rtrim($docblock->render('        '))
                    )
                ]
                : []
        ];

        if ($storage->is_interface) {
            if ($storage->direct_interface_parents) {
                $subnodes['extends'] = [];

                foreach ($storage->direct_interface_parents as $direct_interface_parent) {
                    $subnodes['extends'][] = new PhpParser\Node\Name\FullyQualified($direct_interface_parent);
                }
            }

            return new PhpParser\Node\Stmt\Interface_(
                $classlike_name,
                $subnodes,
                $attrs
            );
        }

        if ($storage->is_trait) {
            return new PhpParser\Node\Stmt\Trait_(
                $classlike_name,
                $subnodes,
                $attrs
            );
        }

        if ($storage->parent_class) {
            $subnodes['extends'] = new PhpParser\Node\Name\FullyQualified($storage->parent_class);
        } else

        if ($storage->direct_class_interfaces) {
            $subnodes['implements'] = [];
            foreach ($storage->direct_class_interfaces as $direct_class_interface) {
                $subnodes['implements'][] = new PhpParser\Node\Name\FullyQualified($direct_class_interface);
            }
        }

        return new PhpParser\Node\Stmt\Class_(
            $classlike_name,
            $subnodes,
            $attrs
        );
    }

    /**
     * @return list<PhpParser\Node\Stmt\ClassConst>
     */
    private static function getConstantNodes(\Psalm\Codebase $codebase, ClassLikeStorage $storage) : array
    {
        $constant_nodes = [];

        foreach ($storage->constants as $constant_name => $constant_storage) {
            if ($constant_storage->unresolved_node) {
                $type = new Type\Union([
                    \Psalm\Internal\Codebase\ConstantTypeResolver::resolve(
                        $codebase->classlikes,
                        $constant_storage->unresolved_node
                    )
                ]);
            } elseif ($constant_storage->type) {
                $type = $constant_storage->type;
            } else {
                throw new \UnexpectedValueException('bad');
            }

            $constant_nodes[] = new PhpParser\Node\Stmt\ClassConst(
                [
                    new PhpParser\Node\Const_(
                        $constant_name,
                        StubsGenerator::getExpressionFromType($type)
                    )
                ],
                $constant_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC
                    ? PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC
                    : ($constant_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PROTECTED
                        ? PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED
                        : PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE)
            );
        }

        return $constant_nodes;
    }

    /**
     * @return list<PhpParser\Node\Stmt\Property>
     */
    private static function getPropertyNodes(ClassLikeStorage $storage) : array
    {
        $namespace_name = implode('\\', array_slice(explode('\\', $storage->name), 0, -1));

        $property_nodes = [];

        foreach ($storage->properties as $property_name => $property_storage) {
            switch ($property_storage->visibility) {
                case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE;
                    break;
                case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED;
                    break;
                default:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC;
                    break;
            }

            $docblock = new ParsedDocblock('', []);

            if ($property_storage->type
                && $property_storage->signature_type !== $property_storage->type
            ) {
                $docblock->tags['var'][] = $property_storage->type->toNamespacedString(
                    $namespace_name,
                    [],
                    null,
                    false
                );
            }

            $property_nodes[] = new PhpParser\Node\Stmt\Property(
                $flag | ($property_storage->is_static ? PhpParser\Node\Stmt\Class_::MODIFIER_STATIC : 0),
                [
                    new PhpParser\Node\Stmt\PropertyProperty(
                        $property_name,
                        $property_storage->suggested_type
                            ? StubsGenerator::getExpressionFromType($property_storage->suggested_type)
                            : null
                    )
                ],
                [
                    'comments' => $docblock->tags
                        ? [
                            new PhpParser\Comment\Doc(
                                \rtrim($docblock->render('        '))
                            )
                        ]
                        : []
                ],
                $property_storage->signature_type
                    ? StubsGenerator::getParserTypeFromPsalmType($property_storage->signature_type)
                    : null
            );
        }

        return $property_nodes;
    }

    /**
     * @return list<PhpParser\Node\Stmt\ClassMethod>
     */
    private static function getMethodNodes(ClassLikeStorage $storage): array {
        $namespace_name = implode('\\', array_slice(explode('\\', $storage->name), 0, -1));
        $method_nodes = [];

        foreach ($storage->methods as $method_storage) {
            if (!$method_storage->cased_name) {
                throw new \UnexpectedValueException('very bad');
            }

            switch ($method_storage->visibility) {
                case \ReflectionProperty::IS_PRIVATE:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PRIVATE;
                    break;
                case \ReflectionProperty::IS_PROTECTED:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PROTECTED;
                    break;
                default:
                    $flag = PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC;
                    break;
            }

            $docblock = new ParsedDocblock('', []);

            foreach ($method_storage->template_types ?: [] as $template_name => $map) {
                $type = array_values($map)[0][0];

                $docblock->tags['template'][] = $template_name . ' as ' . $type->toNamespacedString(
                    $namespace_name,
                    [],
                    null,
                    false
                );
            }

            foreach ($method_storage->params as $param) {
                if ($param->type && $param->type !== $param->signature_type) {
                    $docblock->tags['param'][] = $param->type->toNamespacedString(
                        $namespace_name,
                        [],
                        null,
                        false
                    ) . ' $' . $param->name;
                }
            }

            if ($method_storage->return_type
                && $method_storage->signature_return_type !== $method_storage->return_type
            ) {
                $docblock->tags['return'][] = $method_storage->return_type->toNamespacedString(
                    $namespace_name,
                    [],
                    null,
                    false
                );
            }

            foreach ($method_storage->throws ?: [] as $exception_name => $_) {
                $docblock->tags['throws'][] = Type::getStringFromFQCLN(
                    $exception_name,
                    $namespace_name,
                    [],
                    null,
                    false
                );
            }

            $method_nodes[] = new PhpParser\Node\Stmt\ClassMethod(
                $method_storage->cased_name,
                [
                    'flags' => $flag
                        | ($method_storage->is_static ? PhpParser\Node\Stmt\Class_::MODIFIER_STATIC : 0)
                        | ($method_storage->abstract ? PhpParser\Node\Stmt\Class_::MODIFIER_ABSTRACT : 0),
                    'params' => StubsGenerator::getFunctionParamNodes($method_storage),
                    'returnType' => $method_storage->signature_return_type
                        ? StubsGenerator::getParserTypeFromPsalmType($method_storage->signature_return_type)
                        : null,
                    'stmts' =>  $storage->is_interface || $method_storage->abstract ? null : [],
                ],
                [
                    'comments' => $docblock->tags
                        ? [
                            new PhpParser\Comment\Doc(
                                \rtrim($docblock->render('        '))
                            )
                        ]
                        : []
                ]
            );
        }

        return $method_nodes;
    }
}
