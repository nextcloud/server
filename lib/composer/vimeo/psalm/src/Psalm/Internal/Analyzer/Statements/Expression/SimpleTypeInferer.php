<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\NonDivArithmeticOpAnalyzer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Type;
use function strtolower;
use function count;
use function array_shift;
use function reset;

class SimpleTypeInferer
{
    /**
     * @param   ?array<string, ClassConstantStorage> $existing_class_constants
     */
    public static function infer(
        \Psalm\Codebase $codebase,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        PhpParser\Node\Expr $stmt,
        \Psalm\Aliases $aliases,
        \Psalm\FileSource $file_source = null,
        ?array $existing_class_constants = null,
        ?string $fq_classlike_name = null
    ): ?Type\Union {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                $left = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->left,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );
                $right = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->right,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($left
                    && $right
                ) {
                    if ($left->isSingleStringLiteral()
                        && $right->isSingleStringLiteral()
                    ) {
                        $result = $left->getSingleStringLiteral()->value . $right->getSingleStringLiteral()->value;

                        return Type::getString($result);
                    }

                    if ($left->isString()) {
                        $left_string_types = $left->getAtomicTypes();
                        $left_string_type = reset($left_string_types);
                        if ($left_string_type instanceof Type\Atomic\TNonEmptyString) {
                            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
                        }
                    }

                    if ($right->isString()) {
                        $right_string_types = $right->getAtomicTypes();
                        $right_string_type = reset($right_string_types);
                        if ($right_string_type instanceof Type\Atomic\TNonEmptyString) {
                            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
                        }
                    }
                }

                return Type::getString();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            ) {
                return Type::getBool();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
                return Type::getInt();
            }

            $stmt_left_type = self::infer(
                $codebase,
                $nodes,
                $stmt->left,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            $stmt_right_type = self::infer(
                $codebase,
                $nodes,
                $stmt->right,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$stmt_left_type || !$stmt_right_type) {
                return null;
            }

            $nodes->setType(
                $stmt->left,
                $stmt_left_type
            );

            $nodes->setType(
                $stmt->right,
                $stmt_right_type
            );

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            ) {
                NonDivArithmeticOpAnalyzer::analyze(
                    $file_source instanceof StatementsSource ? $file_source : null,
                    $nodes,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    return $result_type;
                }

                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && ($stmt_left_type->hasInt() || $stmt_left_type->hasFloat())
                && ($stmt_right_type->hasInt() || $stmt_right_type->hasFloat())
            ) {
                return Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (strtolower($stmt->name->parts[0]) === 'false') {
                return Type::getFalse();
            } elseif (strtolower($stmt->name->parts[0]) === 'true') {
                return Type::getTrue();
            } elseif (strtolower($stmt->name->parts[0]) === 'null') {
                return Type::getNull();
            } elseif ($stmt->name->parts[0] === '__NAMESPACE__') {
                return Type::getString($aliases->namespace);
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\File
        ) {
            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Line) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Class_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Method
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Trait_
            || $stmt instanceof PhpParser\Node\Scalar\MagicConst\Function_
        ) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Namespace_) {
            return Type::getString($aliases->namespace);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && $stmt->class->parts !== ['parent']
            ) {
                if (isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    if ($stmt->class->parts === ['self']) {
                        return clone $existing_class_constants[$stmt->name->name]->type;
                    }
                }

                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    $const_fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $stmt->class,
                        $aliases
                    );
                }

                if (strtolower($const_fq_class_name) === strtolower($fq_classlike_name)
                    && isset($existing_class_constants[$stmt->name->name])
                    && $existing_class_constants[$stmt->name->name]->type
                ) {
                    return clone $existing_class_constants[$stmt->name->name]->type;
                }

                if (strtolower($stmt->name->name) === 'class') {
                    return Type::getLiteralClassString($const_fq_class_name);
                }

                if ($existing_class_constants === null
                    && $file_source instanceof StatementsAnalyzer
                ) {
                    try {
                        $foreign_class_constant = $codebase->classlikes->getClassConstantType(
                            $const_fq_class_name,
                            $stmt->name->name,
                            \ReflectionProperty::IS_PRIVATE,
                            $file_source
                        );

                        if ($foreign_class_constant) {
                            return clone $foreign_class_constant;
                        }

                        return null;
                    } catch (\InvalidArgumentException $e) {
                        return null;
                    } catch (\Psalm\Exception\CircularReferenceException $e) {
                        return null;
                    }
                }
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && strtolower($stmt->name->name) === 'class') {
                return Type::getClassString();
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt(false, $stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (count($stmt->items) === 0) {
                return Type::getEmptyArray();
            }

            $item_key_type = null;
            $item_value_type = null;

            $property_types = [];
            $class_strings = [];

            $can_create_objectlike = true;

            $is_list = true;

            foreach ($stmt->items as $int_offset => $item) {
                if ($item === null) {
                    continue;
                }

                $single_item_key_type = null;

                if ($item->key) {
                    $single_item_key_type = self::infer(
                        $codebase,
                        $nodes,
                        $item->key,
                        $aliases,
                        $file_source,
                        $existing_class_constants,
                        $fq_classlike_name
                    );

                    if ($single_item_key_type) {
                        if ($item_key_type) {
                            $item_key_type = Type::combineUnionTypes(
                                $single_item_key_type,
                                $item_key_type,
                                null,
                                false,
                                true,
                                30
                            );
                        } else {
                            $item_key_type = $single_item_key_type;
                        }
                    }
                } else {
                    $item_key_type = Type::getInt();
                }

                $single_item_value_type = self::infer(
                    $codebase,
                    $nodes,
                    $item->value,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if (!$single_item_value_type) {
                    return null;
                }

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    if (count($property_types) <= 50) {
                        $property_types[$item->key ? $item->key->value : $int_offset] = $single_item_value_type;
                    } else {
                        $can_create_objectlike = false;
                    }

                    if ($item->key
                        && (!$item->key instanceof PhpParser\Node\Scalar\LNumber
                            || $item->key->value !== $int_offset)
                    ) {
                        $is_list = false;
                    }
                } else {
                    $is_list = false;
                    $dim_type = $single_item_key_type;

                    if (!$dim_type) {
                        return null;
                    }

                    $dim_atomic_types = $dim_type->getAtomicTypes();

                    if (count($dim_atomic_types) > 1 || $dim_type->hasMixed() || count($property_types) > 50) {
                        $can_create_objectlike = false;
                    } else {
                        $atomic_type = array_shift($dim_atomic_types);

                        if ($atomic_type instanceof Type\Atomic\TLiteralInt
                            || $atomic_type instanceof Type\Atomic\TLiteralString
                        ) {
                            if ($atomic_type instanceof Type\Atomic\TLiteralClassString) {
                                $class_strings[$atomic_type->value] = true;
                            }

                            $property_types[$atomic_type->value] = $single_item_value_type;
                        } else {
                            $can_create_objectlike = false;
                        }
                    }
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes(
                        $single_item_value_type,
                        $item_value_type,
                        null,
                        false,
                        true,
                        30
                    );
                } else {
                    $item_value_type = $single_item_value_type;
                }
            }

            // if this array looks like an object-like array, let's return that instead
            if ($item_value_type
                && $item_key_type
                && ($item_key_type->hasString() || $item_key_type->hasInt())
                && $can_create_objectlike
                && $property_types
            ) {
                $objectlike = new Type\Atomic\TKeyedArray($property_types, $class_strings);
                $objectlike->sealed = true;
                $objectlike->is_list = $is_list;
                return new Type\Union([$objectlike]);
            }

            if (!$item_key_type || !$item_value_type) {
                return null;
            }

            return new Type\Union([
                new Type\Atomic\TNonEmptyArray([
                    $item_key_type,
                    $item_value_type,
                ]),
            ]);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            return Type::getFloat();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            return Type::getObject();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            return Type::getArray();
        }

        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            $type_to_invert = self::infer(
                $codebase,
                $nodes,
                $stmt->expr,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$type_to_invert) {
                return null;
            }

            foreach ($type_to_invert->getAtomicTypes() as $type_part) {
                if ($type_part instanceof Type\Atomic\TLiteralInt
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                } elseif ($type_part instanceof Type\Atomic\TLiteralFloat
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                }
            }

            return $type_to_invert;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if ($stmt->var instanceof PhpParser\Node\Expr\ClassConstFetch
                && $stmt->dim
            ) {
                $array_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->var,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                $dim_type = self::infer(
                    $codebase,
                    $nodes,
                    $stmt->dim,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if ($array_type !== null && $dim_type !== null) {
                    if ($dim_type->isSingleStringLiteral()) {
                        $dim_value = $dim_type->getSingleStringLiteral()->value;
                    } elseif ($dim_type->isSingleIntLiteral()) {
                        $dim_value = $dim_type->getSingleIntLiteral()->value;
                    } else {
                        return null;
                    }

                    foreach ($array_type->getAtomicTypes() as $array_atomic_type) {
                        if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            if (isset($array_atomic_type->properties[$dim_value])) {
                                return clone $array_atomic_type->properties[$dim_value];
                            }

                            return null;
                        }
                    }
                }
            }
        }

        return null;
    }
}
