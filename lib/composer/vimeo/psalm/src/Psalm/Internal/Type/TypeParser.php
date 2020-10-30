<?php
namespace Psalm\Internal\Type;

use Psalm\Type\TypeNode;
use function array_keys;
use function array_map;
use function array_shift;
use function array_unshift;
use function array_values;
use function count;
use function explode;
use function get_class;
use function in_array;
use function preg_match;
use function preg_replace;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Union;
use function array_key_exists;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

class TypeParser
{
    /**
     * Parses a string type representation
     *
     * @param  list<array{0: string, 1: int}> $type_tokens
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Union}>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     */
    public static function parseTokens(
        array $type_tokens,
        ?array $php_version = null,
        array $template_type_map = [],
        array $type_aliases = []
    ): Union {
        if (count($type_tokens) === 1) {
            $only_token = $type_tokens[0];

            // Note: valid identifiers can include class names or $this
            if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $only_token[0])) {
                if (!\is_numeric($only_token[0])
                    && strpos($only_token[0], '\'') !== false
                    && strpos($only_token[0], '"') !== false
                ) {
                    throw new TypeParseTreeException("Invalid type '$only_token[0]'");
                }
            } else {
                $only_token[0] = TypeTokenizer::fixScalarTerms($only_token[0], $php_version);

                $atomic = Atomic::create($only_token[0], $php_version, $template_type_map, $type_aliases);
                $atomic->offset_start = 0;
                $atomic->offset_end = strlen($only_token[0]);

                return new Union([$atomic]);
            }
        }

        $parse_tree = (new ParseTreeCreator($type_tokens))->create();
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();
        $parsed_type = self::getTypeFromTree(
            $parse_tree,
            $codebase,
            $php_version,
            $template_type_map,
            $type_aliases
        );

        if (!($parsed_type instanceof Union)) {
            $parsed_type = new Union([$parsed_type]);
        }

        return $parsed_type;
    }

    /**
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Union}>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     *
     * @return  Atomic|Union
     */
    public static function getTypeFromTree(
        ParseTree $parse_tree,
        Codebase $codebase,
        ?array $php_version = null,
        array $template_type_map = [],
        array $type_aliases = []
    ): TypeNode {
        if ($parse_tree instanceof ParseTree\GenericTree) {
            $generic_type = $parse_tree->value;

            $generic_params = [];

            foreach ($parse_tree->children as $i => $child_tree) {
                $tree_type = self::getTypeFromTree(
                    $child_tree,
                    $codebase,
                    null,
                    $template_type_map,
                    $type_aliases
                );

                if ($generic_type === 'class-string-map'
                    && $i === 0
                ) {
                    if ($tree_type instanceof TTemplateParam) {
                        $template_type_map[$tree_type->param_name] = ['class-string-map' => [$tree_type->as]];
                    } elseif ($tree_type instanceof TNamedObject) {
                        $template_type_map[$tree_type->value] = ['class-string-map' => [\Psalm\Type::getObject()]];
                    }
                }

                $generic_params[] = $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);
            }

            $generic_type_value = TypeTokenizer::fixScalarTerms($generic_type);

            if (($generic_type_value === 'array'
                    || $generic_type_value === 'non-empty-array'
                    || $generic_type_value === 'associative-array')
                && count($generic_params) === 1
            ) {
                array_unshift($generic_params, new Union([new TArrayKey]));
            } elseif (count($generic_params) === 1
                && in_array(
                    $generic_type_value,
                    ['iterable', 'Traversable', 'Iterator', 'IteratorAggregate', 'arraylike-object'],
                    true
                )
            ) {
                array_unshift($generic_params, new Union([new TMixed]));
            } elseif ($generic_type_value === 'Generator') {
                if (count($generic_params) === 1) {
                    array_unshift($generic_params, new Union([new TMixed]));
                }

                for ($i = 0, $l = 4 - count($generic_params); $i < $l; ++$i) {
                    $generic_params[] = new Union([new TMixed]);
                }
            }

            if (!$generic_params) {
                throw new TypeParseTreeException('No generic params provided for type');
            }

            if ($generic_type_value === 'array' || $generic_type_value === 'associative-array') {
                if ($generic_params[0]->isMixed()) {
                    $generic_params[0] = \Psalm\Type::getArrayKey();
                }

                return new TArray($generic_params);
            }

            if ($generic_type_value === 'arraylike-object') {
                $traversable = new TGenericObject('Traversable', $generic_params);
                $array_acccess = new TGenericObject('ArrayAccess', $generic_params);
                $traversable->extra_types[$array_acccess->getKey()] = $array_acccess;

                return $traversable;
            }

            if ($generic_type_value === 'non-empty-array') {
                if ($generic_params[0]->isMixed()) {
                    $generic_params[0] = \Psalm\Type::getArrayKey();
                }

                return new TNonEmptyArray($generic_params);
            }

            if ($generic_type_value === 'iterable') {
                return new TIterable($generic_params);
            }

            if ($generic_type_value === 'list') {
                return new TList($generic_params[0]);
            }

            if ($generic_type_value === 'non-empty-list') {
                return new TNonEmptyList($generic_params[0]);
            }

            if ($generic_type_value === 'class-string') {
                $class_name = (string) $generic_params[0];

                if (isset($template_type_map[$class_name])) {
                    $first_class = array_keys($template_type_map[$class_name])[0];

                    return self::getGenericParamClass(
                        $class_name,
                        $template_type_map[$class_name][$first_class][0],
                        $first_class
                    );
                }

                $param_union_types = array_values($generic_params[0]->getAtomicTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in class string param');
                }

                if (!$param_union_types[0] instanceof TNamedObject) {
                    throw new TypeParseTreeException('Class string param should be a named object');
                }

                return new TClassString($class_name, $param_union_types[0]);
            }

            if ($generic_type_value === 'class-string-map') {
                if (count($generic_params) !== 2) {
                    throw new TypeParseTreeException(
                        'There should only be two params for class-string-map, '
                            . count($generic_params) . ' provided'
                    );
                }

                $template_marker_parts = array_values($generic_params[0]->getAtomicTypes());

                $template_marker = $template_marker_parts[0];

                $template_as_type = null;

                if ($template_marker instanceof TNamedObject) {
                    $template_param_name = $template_marker->value;
                } elseif ($template_marker instanceof Atomic\TTemplateParam) {
                    $template_param_name = $template_marker->param_name;
                    $template_as_type = array_values($template_marker->as->getAtomicTypes())[0];

                    if (!$template_as_type instanceof TNamedObject) {
                        throw new TypeParseTreeException(
                            'Unrecognised as type'
                        );
                    }
                } else {
                    throw new TypeParseTreeException(
                        'Unrecognised class-string-map templated param'
                    );
                }

                return new TClassStringMap(
                    $template_param_name,
                    $template_as_type,
                    $generic_params[1]
                );
            }

            if ($generic_type_value === 'key-of') {
                $param_name = (string) $generic_params[0];

                if (isset($template_type_map[$param_name])) {
                    $defining_class = array_keys($template_type_map[$param_name])[0];

                    return new Atomic\TTemplateKeyOf(
                        $param_name,
                        $defining_class,
                        $template_type_map[$param_name][$defining_class][0]
                    );
                }

                $param_union_types = array_values($generic_params[0]->getAtomicTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in key-of type');
                }

                if (!$param_union_types[0] instanceof Atomic\TScalarClassConstant) {
                    throw new TypeParseTreeException(
                        'Untemplated key-of param ' . $param_name . ' should be a class constant'
                    );
                }

                return new Atomic\TKeyOfClassConstant(
                    $param_union_types[0]->fq_classlike_name,
                    $param_union_types[0]->const_name
                );
            }

            if ($generic_type_value === 'value-of') {
                $param_name = (string) $generic_params[0];

                if (isset($template_type_map[$param_name])) {
                    $defining_class = array_keys($template_type_map[$param_name])[0];

                    return new Atomic\TTemplateKeyOf(
                        $param_name,
                        $defining_class,
                        $template_type_map[$param_name][$defining_class][0]
                    );
                }

                $param_union_types = array_values($generic_params[0]->getAtomicTypes());

                if (count($param_union_types) > 1) {
                    throw new TypeParseTreeException('Union types are not allowed in value-of type');
                }

                if (!$param_union_types[0] instanceof Atomic\TScalarClassConstant) {
                    throw new TypeParseTreeException(
                        'Untemplated value-of param ' . $param_name . ' should be a class constant'
                    );
                }

                return new Atomic\TValueOfClassConstant(
                    $param_union_types[0]->fq_classlike_name,
                    $param_union_types[0]->const_name
                );
            }

            if (isset(TypeTokenizer::PSALM_RESERVED_WORDS[$generic_type_value])
                && $generic_type_value !== 'self'
                && $generic_type_value !== 'static'
            ) {
                throw new TypeParseTreeException('Cannot create generic object with reserved word');
            }

            return new TGenericObject($generic_type_value, $generic_params);
        }

        if ($parse_tree instanceof ParseTree\UnionTree) {
            $has_null = false;

            $atomic_types = [];

            foreach ($parse_tree->children as $child_tree) {
                if ($child_tree instanceof ParseTree\NullableTree) {
                    if (!isset($child_tree->children[0])) {
                        throw new TypeParseTreeException('Invalid ? character');
                    }

                    $atomic_type = self::getTypeFromTree(
                        $child_tree->children[0],
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases
                    );
                    $has_null = true;
                } else {
                    $atomic_type = self::getTypeFromTree(
                        $child_tree,
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases
                    );
                }

                if ($atomic_type instanceof Union) {
                    foreach ($atomic_type->getAtomicTypes() as $type) {
                        $atomic_types[] = $type;
                    }

                    continue;
                }

                $atomic_types[] = $atomic_type;
            }

            if ($has_null) {
                $atomic_types[] = new TNull;
            }

            if (!$atomic_types) {
                throw new TypeParseTreeException(
                    'No atomic types found'
                );
            }

            return TypeCombination::combineTypes($atomic_types);
        }

        if ($parse_tree instanceof ParseTree\IntersectionTree) {
            $intersection_types = array_map(
                /**
                 * @return Atomic
                 */
                function (ParseTree $child_tree) use ($codebase, $template_type_map, $type_aliases) {
                    $atomic_type = self::getTypeFromTree(
                        $child_tree,
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases
                    );

                    if (!$atomic_type instanceof Atomic) {
                        throw new TypeParseTreeException(
                            'Intersection types cannot contain unions'
                        );
                    }

                    return $atomic_type;
                },
                $parse_tree->children
            );

            $first_type = \reset($intersection_types);
            $last_type = \end($intersection_types);

            $onlyTKeyedArray = $first_type instanceof TKeyedArray
                || $last_type instanceof TKeyedArray;

            foreach ($intersection_types as $intersection_type) {
                if (!$intersection_type instanceof TKeyedArray
                    && ($intersection_type !== $first_type
                        || !$first_type instanceof TArray)
                    && ($intersection_type !== $last_type
                        || !$last_type instanceof TArray)
                ) {
                    $onlyTKeyedArray = false;
                    break;
                }
            }

            if ($onlyTKeyedArray) {
                /** @var non-empty-array<string|int, Union> */
                $properties = [];

                if ($first_type instanceof TArray) {
                    \array_shift($intersection_types);
                } elseif ($last_type instanceof TArray) {
                    \array_pop($intersection_types);
                }

                /** @var TKeyedArray $intersection_type */
                foreach ($intersection_types as $intersection_type) {
                    foreach ($intersection_type->properties as $property => $property_type) {
                        if (!array_key_exists($property, $properties)) {
                            $properties[$property] = clone $property_type;
                            continue;
                        }

                        $intersection_type = \Psalm\Type::intersectUnionTypes(
                            $properties[$property],
                            $property_type,
                            $codebase
                        );
                        if ($intersection_type === null) {
                            throw new TypeParseTreeException(
                                'Incompatible intersection types for "' . $property . '", '
                                    . $properties[$property] . ' and ' . $property_type
                                    . ' provided'
                            );
                        }
                        $properties[$property] = $intersection_type;
                    }
                }

                $keyed_array = new TKeyedArray($properties);

                if ($first_type instanceof TArray) {
                    $keyed_array->previous_key_type = $first_type->type_params[0];
                    $keyed_array->previous_value_type = $first_type->type_params[1];
                } elseif ($last_type instanceof TArray) {
                    $keyed_array->previous_key_type = $last_type->type_params[0];
                    $keyed_array->previous_value_type = $last_type->type_params[1];
                }

                return $keyed_array;
            }

            $keyed_intersection_types = [];

            if ($intersection_types[0] instanceof TTypeAlias) {
                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TTypeAlias) {
                        throw new TypeParseTreeException(
                            'Intersection types with a type alias can only be comprised of other type aliases, '
                                . get_class($intersection_type) . ' provided'
                        );
                    }

                    $keyed_intersection_types[$intersection_type->getKey()] = $intersection_type;
                }

                $first_type = array_shift($keyed_intersection_types);

                if ($keyed_intersection_types) {
                    $first_type->extra_types = $keyed_intersection_types;
                }
            } else {
                foreach ($intersection_types as $intersection_type) {
                    if (!$intersection_type instanceof TIterable
                        && !$intersection_type instanceof TNamedObject
                        && !$intersection_type instanceof TTemplateParam
                        && !$intersection_type instanceof TObjectWithProperties
                    ) {
                        throw new TypeParseTreeException(
                            'Intersection types must be all objects or all object-like arrays, '
                                . get_class($intersection_type) . ' provided'
                        );
                    }

                    $keyed_intersection_types[
                        $intersection_type instanceof TIterable
                            ? $intersection_type->getId()
                            : $intersection_type->getKey()
                        ] = $intersection_type;
                }

                $intersect_static = false;

                if (isset($keyed_intersection_types['static'])) {
                    unset($keyed_intersection_types['static']);
                    $intersect_static = true;
                }

                if (!$keyed_intersection_types && $intersect_static) {
                    return new TNamedObject('static');
                }

                $first_type = array_shift($keyed_intersection_types);

                if ($intersect_static
                    && $first_type instanceof TNamedObject
                ) {
                    $first_type->was_static = true;
                }

                if ($keyed_intersection_types) {
                    $first_type->extra_types = $keyed_intersection_types;
                }
            }

            return $first_type;
        }

        if ($parse_tree instanceof ParseTree\KeyedArrayTree) {
            $properties = [];

            $type = $parse_tree->value;

            $is_tuple = true;

            foreach ($parse_tree->children as $i => $property_branch) {
                if (!$property_branch instanceof ParseTree\KeyedArrayPropertyTree) {
                    $property_type = self::getTypeFromTree(
                        $property_branch,
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases
                    );
                    $property_maybe_undefined = false;
                    $property_key = (string)$i;
                } elseif (count($property_branch->children) === 1) {
                    $property_type = self::getTypeFromTree(
                        $property_branch->children[0],
                        $codebase,
                        null,
                        $template_type_map,
                        $type_aliases
                    );
                    $property_maybe_undefined = $property_branch->possibly_undefined;
                    $property_key = $property_branch->value;
                    $is_tuple = false;
                } else {
                    throw new TypeParseTreeException(
                        'Missing property type'
                    );
                }

                if ($property_key[0] === '\'' || $property_key[0] === '"') {
                    $property_key = \stripslashes(substr($property_key, 1, -1));
                }

                if (!$property_type instanceof Union) {
                    $property_type = new Union([$property_type]);
                }

                if ($property_maybe_undefined) {
                    $property_type->possibly_undefined = true;
                }

                $properties[$property_key] = $property_type;
            }

            if ($type !== 'array' && $type !== 'object' && $type !== 'callable-array') {
                throw new TypeParseTreeException('Unexpected brace character');
            }

            if (!$properties) {
                throw new TypeParseTreeException('No properties supplied for TKeyedArray');
            }

            if ($type === 'object') {
                return new TObjectWithProperties($properties);
            }

            if ($type === 'callable-array') {
                return new Atomic\TCallableKeyedArray($properties);
            }

            $object_like = new TKeyedArray($properties);

            if ($is_tuple) {
                $object_like->sealed = true;
                $object_like->is_list = true;
            }

            return $object_like;
        }

        if ($parse_tree instanceof ParseTree\CallableWithReturnTypeTree) {
            $callable_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            if (!$callable_type instanceof TCallable && !$callable_type instanceof TClosure) {
                throw new \InvalidArgumentException('Parsing callable tree node should return TCallable');
            }

            if (!isset($parse_tree->children[1])) {
                throw new TypeParseTreeException('Invalid return type');
            }

            $return_type = self::getTypeFromTree(
                $parse_tree->children[1],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            $callable_type->return_type = $return_type instanceof Union ? $return_type : new Union([$return_type]);

            return $callable_type;
        }

        if ($parse_tree instanceof ParseTree\CallableTree) {
            $params = array_map(
                /**
                 * @return FunctionLikeParameter
                 */
                function (ParseTree $child_tree) use (
                    $codebase,
                    $template_type_map,
                    $type_aliases
                ): FunctionLikeParameter {
                    $is_variadic = false;
                    $is_optional = false;

                    if ($child_tree instanceof ParseTree\CallableParamTree) {
                        if (isset($child_tree->children[0])) {
                            $tree_type = self::getTypeFromTree(
                                $child_tree->children[0],
                                $codebase,
                                null,
                                $template_type_map,
                                $type_aliases
                            );
                        } else {
                            $tree_type = new TMixed();
                        }

                        $is_variadic = $child_tree->variadic;
                        $is_optional = $child_tree->has_default;
                    } else {
                        if ($child_tree instanceof ParseTree\Value && strpos($child_tree->value, '$') > 0) {
                            $child_tree->value = preg_replace('/(.+)\$.*/', '$1', $child_tree->value);
                        }

                        $tree_type = self::getTypeFromTree(
                            $child_tree,
                            $codebase,
                            null,
                            $template_type_map,
                            $type_aliases
                        );
                    }

                    $tree_type = $tree_type instanceof Union ? $tree_type : new Union([$tree_type]);

                    $param = new FunctionLikeParameter(
                        '',
                        false,
                        $tree_type,
                        null,
                        null,
                        $is_optional,
                        false,
                        $is_variadic
                    );

                    // type is not authoratative
                    $param->signature_type = null;

                    return $param;
                },
                $parse_tree->children
            );
            $pure = strpos($parse_tree->value, 'pure-') === 0 ? true : null;

            if (in_array(strtolower($parse_tree->value), ['closure', '\closure'], true)) {
                return new TClosure('Closure', $params, null, $pure);
            }

            return new TCallable('callable', $params, null, $pure);
        }

        if ($parse_tree instanceof ParseTree\EncapsulationTree) {
            return self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );
        }

        if ($parse_tree instanceof ParseTree\NullableTree) {
            if (!isset($parse_tree->children[0])) {
                throw new TypeParseTreeException('Misplaced question mark');
            }

            $non_nullable_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            if ($non_nullable_type instanceof Union) {
                $non_nullable_type->addType(new TNull);

                return $non_nullable_type;
            }

            return TypeCombination::combineTypes([
                new TNull,
                $non_nullable_type,
            ]);
        }

        if ($parse_tree instanceof ParseTree\MethodTree
            || $parse_tree instanceof ParseTree\MethodWithReturnTypeTree
        ) {
            throw new TypeParseTreeException('Misplaced brackets');
        }

        if ($parse_tree instanceof ParseTree\IndexedAccessTree) {
            if (!isset($parse_tree->children[0]) || !$parse_tree->children[0] instanceof ParseTree\Value) {
                throw new TypeParseTreeException('Unrecognised indexed access');
            }

            $offset_param_name = $parse_tree->value;
            $array_param_name = $parse_tree->children[0]->value;

            if (!isset($template_type_map[$offset_param_name])) {
                throw new TypeParseTreeException('Unrecognised template param ' . $offset_param_name);
            }

            if (!isset($template_type_map[$array_param_name])) {
                throw new TypeParseTreeException('Unrecognised template param ' . $array_param_name);
            }

            $offset_template_data = $template_type_map[$offset_param_name];

            $offset_defining_class = array_keys($offset_template_data)[0];

            if (!$offset_defining_class
                && isset($offset_template_data[''])
                && $offset_template_data[''][0]->isSingle()
            ) {
                $offset_template_type = array_values($offset_template_data[''][0]->getAtomicTypes())[0];

                if ($offset_template_type instanceof Atomic\TTemplateKeyOf) {
                    $offset_defining_class = (string) $offset_template_type->defining_class;
                }
            }

            $array_defining_class = array_keys($template_type_map[$array_param_name])[0];

            if ($offset_defining_class !== $array_defining_class
                && substr($offset_defining_class, 0, 3) !== 'fn-'
            ) {
                throw new TypeParseTreeException('Template params are defined in different locations');
            }

            return new Atomic\TTemplateIndexedAccess(
                $array_param_name,
                $offset_param_name,
                $array_defining_class
            );
        }

        if ($parse_tree instanceof ParseTree\TemplateAsTree) {
            return new Atomic\TTemplateParam(
                $parse_tree->param_name,
                new Union([new TNamedObject($parse_tree->as)]),
                'class-string-map'
            );
        }

        if ($parse_tree instanceof ParseTree\ConditionalTree) {
            $template_param_name = $parse_tree->condition->param_name;

            if (!isset($template_type_map[$template_param_name])) {
                throw new TypeParseTreeException('Unrecognized template \'' . $template_param_name . '\'');
            }

            if (count($parse_tree->children) !== 2) {
                throw new TypeParseTreeException('Invalid conditional');
            }

            $first_class = array_keys($template_type_map[$template_param_name])[0];

            $conditional_type = self::getTypeFromTree(
                $parse_tree->condition->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            $if_type = self::getTypeFromTree(
                $parse_tree->children[0],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            $else_type = self::getTypeFromTree(
                $parse_tree->children[1],
                $codebase,
                null,
                $template_type_map,
                $type_aliases
            );

            if ($conditional_type instanceof Atomic) {
                $conditional_type = new Union([$conditional_type]);
            }

            if ($if_type instanceof Atomic) {
                $if_type = new Union([$if_type]);
            }

            if ($else_type instanceof Atomic) {
                $else_type = new Union([$else_type]);
            }

            return new Atomic\TConditional(
                $template_param_name,
                $first_class,
                $template_type_map[$template_param_name][$first_class][0],
                $conditional_type,
                $if_type,
                $else_type
            );
        }

        if (!$parse_tree instanceof ParseTree\Value) {
            throw new \InvalidArgumentException('Unrecognised parse tree type ' . get_class($parse_tree));
        }

        if ($parse_tree->value[0] === '"' || $parse_tree->value[0] === '\'') {
            return new TLiteralString(substr($parse_tree->value, 1, -1));
        }

        if (strpos($parse_tree->value, '::')) {
            [$fq_classlike_name, $const_name] = explode('::', $parse_tree->value);

            if (isset($template_type_map[$fq_classlike_name]) && $const_name === 'class') {
                $first_class = array_keys($template_type_map[$fq_classlike_name])[0];

                return self::getGenericParamClass(
                    $fq_classlike_name,
                    $template_type_map[$fq_classlike_name][$first_class][0],
                    $first_class
                );
            }

            if ($const_name === 'class') {
                return new Atomic\TLiteralClassString($fq_classlike_name);
            }

            return new Atomic\TScalarClassConstant($fq_classlike_name, $const_name);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)(\.[0-9]{1,})$/', $parse_tree->value)) {
            return new TLiteralFloat((float) $parse_tree->value);
        }

        if (preg_match('/^\-?(0|[1-9][0-9]*)$/', $parse_tree->value)) {
            return new TLiteralInt((int) $parse_tree->value);
        }

        if (!preg_match('@^(\$this|\\\\?[a-zA-Z_\x7f-\xff][\\\\\-0-9a-zA-Z_\x7f-\xff]*)$@', $parse_tree->value)) {
            throw new TypeParseTreeException('Invalid type \'' . $parse_tree->value . '\'');
        }

        $atomic_type_string = TypeTokenizer::fixScalarTerms($parse_tree->value, $php_version);

        $atomic_type = Atomic::create($atomic_type_string, $php_version, $template_type_map, $type_aliases);

        $atomic_type->offset_start = $parse_tree->offset_start;
        $atomic_type->offset_end = $parse_tree->offset_end;

        return $atomic_type;
    }

    private static function getGenericParamClass(
        string $param_name,
        Union $as,
        string $defining_class
    ) : Atomic\TTemplateParamClass {
        if ($as->hasMixed()) {
            return new Atomic\TTemplateParamClass(
                $param_name,
                'object',
                null,
                $defining_class
            );
        }

        if (!$as->isSingle()) {
            throw new TypeParseTreeException(
                'Invalid templated classname \'' . $as . '\''
            );
        }

        foreach ($as->getAtomicTypes() as $t) {
            if ($t instanceof TObject) {
                return new Atomic\TTemplateParamClass(
                    $param_name,
                    'object',
                    null,
                    $defining_class
                );
            }

            if ($t instanceof TIterable) {
                $traversable = new TGenericObject(
                    'Traversable',
                    $t->type_params
                );

                $as->substitute(new Union([$t]), new Union([$traversable]));

                return new Atomic\TTemplateParamClass(
                    $param_name,
                    $traversable->value,
                    $traversable,
                    $defining_class
                );
            }

            if ($t instanceof Atomic\TTemplateParam) {
                $t_atomic_types = $t->as->getAtomicTypes();
                $t_atomic_type = \count($t_atomic_types) === 1 ? \reset($t_atomic_types) : null;

                if (!$t_atomic_type instanceof TNamedObject) {
                    $t_atomic_type = null;
                }

                return new Atomic\TTemplateParamClass(
                    $t->param_name,
                    $t_atomic_type ? $t_atomic_type->value : 'object',
                    $t_atomic_type,
                    $t->defining_class
                );
            }

            if (!$t instanceof TNamedObject) {
                throw new TypeParseTreeException(
                    'Invalid templated classname \'' . $t->getId() . '\''
                );
            }

            return new Atomic\TTemplateParamClass(
                $param_name,
                $t->value,
                $t,
                $defining_class
            );
        }

        throw new \LogicException('Should never get here');
    }
}
