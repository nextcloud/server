<?php

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type\Union;
use Psalm\Type\Atomic;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use function array_merge;
use function array_values;
use function count;
use function is_string;
use function strpos;
use function substr;

class UnionTemplateHandler
{
    public static function replaceTemplateTypesWithStandins(
        Union $union_type,
        TemplateResult $template_result,
        ?Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        ?Union $input_type,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : Union {
        $atomic_types = [];

        $original_atomic_types = $union_type->getAtomicTypes();

        $had_template = false;

        foreach ($original_atomic_types as $key => $atomic_type) {
            $atomic_types = array_merge(
                $atomic_types,
                self::handleAtomicStandin(
                    $atomic_type,
                    $key,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    $add_upper_bound,
                    $depth,
                    count($original_atomic_types) === 1,
                    $union_type->isNullable(),
                    $had_template
                )
            );
        }

        if ($replace) {
            if (array_values($original_atomic_types) === $atomic_types) {
                return $union_type;
            }

            if (!$atomic_types) {
                throw new \UnexpectedValueException('Cannot remove all keys');
            }

            $new_union_type = new Union($atomic_types);
            $new_union_type->ignore_nullable_issues = $union_type->ignore_nullable_issues;
            $new_union_type->ignore_falsable_issues = $union_type->ignore_falsable_issues;
            $new_union_type->possibly_undefined = $union_type->possibly_undefined;

            if ($had_template) {
                $new_union_type->had_template = true;
            }

            return $new_union_type;
        }

        return $union_type;
    }

    /**
     * @return list<Atomic>
     */
    private static function handleAtomicStandin(
        Atomic $atomic_type,
        string $key,
        TemplateResult $template_result,
        ?Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        ?Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        bool $replace,
        bool $add_upper_bound,
        int $depth,
        bool $was_single,
        bool $was_nullable,
        bool &$had_template
    ) : array {
        if ($bracket_pos = strpos($key, '<')) {
            $key = substr($key, 0, $bracket_pos);
        }

        if ($atomic_type instanceof Atomic\TTemplateParam
            && isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])
        ) {
            $a = self::handleTemplateParamStandin(
                $atomic_type,
                $key,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $template_result,
                $codebase,
                $statements_analyzer,
                $replace,
                $add_upper_bound,
                $depth,
                $was_nullable,
                $had_template
            );

            return $a;
        }

        if ($atomic_type instanceof Atomic\TTemplateParamClass
            && isset($template_result->template_types[$atomic_type->param_name])
        ) {
            if ($replace) {
                return self::handleTemplateParamClassStandin(
                    $atomic_type,
                    $input_type,
                    $input_arg_offset,
                    $template_result,
                    $depth,
                    $was_single
                );
            }
        }

        if ($atomic_type instanceof Atomic\TTemplateIndexedAccess) {
            if ($replace) {
                $atomic_types = [];

                $include_first = true;

                if (isset($template_result->template_types[$atomic_type->array_param_name][$atomic_type->defining_class])
                    && !empty($template_result->upper_bounds[$atomic_type->offset_param_name])
                ) {
                    $array_template_type
                        = $template_result->template_types[$atomic_type->array_param_name][$atomic_type->defining_class][0];
                    $offset_template_type
                        = array_values(
                            $template_result->upper_bounds[$atomic_type->offset_param_name]
                        )[0][0];

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = array_values($array_template_type->getAtomicTypes())[0];
                        $offset_template_type = array_values($offset_template_type->getAtomicTypes())[0];

                        if ($array_template_type instanceof Atomic\TKeyedArray
                            && ($offset_template_type instanceof Atomic\TLiteralString
                                || $offset_template_type instanceof Atomic\TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $include_first = false;

                            $replacement_type
                                = clone $array_template_type->properties[$offset_template_type->value];

                            foreach ($replacement_type->getAtomicTypes() as $replacement_atomic_type) {
                                $atomic_types[] = $replacement_atomic_type;
                            }
                        }
                    }
                }

                if ($include_first) {
                    $atomic_types[] = $atomic_type;
                }

                return $atomic_types;
            }

            return [$atomic_type];
        }

        if ($atomic_type instanceof Atomic\TTemplateKeyOf) {
            if ($replace) {
                $atomic_types = [];

                $include_first = true;

                if (isset($template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class])) {
                    $template_type
                        = $template_result->template_types[$atomic_type->param_name][$atomic_type->defining_class][0];

                    if ($template_type->isSingle()) {
                        $template_type = array_values($template_type->getAtomicTypes())[0];

                        if ($template_type instanceof Atomic\TKeyedArray
                            || $template_type instanceof Atomic\TArray
                            || $template_type instanceof Atomic\TList
                        ) {
                            if ($template_type instanceof Atomic\TKeyedArray) {
                                $key_type = $template_type->getGenericKeyType();
                            } elseif ($template_type instanceof Atomic\TList) {
                                $key_type = \Psalm\Type::getInt();
                            } else {
                                $key_type = clone $template_type->type_params[0];
                            }

                            $include_first = false;

                            foreach ($key_type->getAtomicTypes() as $key_atomic_type) {
                                $atomic_types[] = $key_atomic_type;
                            }
                        }
                    }
                }

                if ($include_first) {
                    $atomic_types[] = $atomic_type;
                }

                return $atomic_types;
            }

            return [$atomic_type];
        }

        $matching_atomic_types = [];

        if ($input_type && $codebase && !$input_type->hasMixed()) {
            $matching_atomic_types = self::findMatchingAtomicTypesForTemplate(
                $input_type,
                $atomic_type,
                $key,
                $codebase,
                $statements_analyzer
            );
        }

        if (!$matching_atomic_types) {
            $atomic_type = $atomic_type->replaceTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                null,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth + 1
            );

            return [$atomic_type];
        }

        $atomic_types = [];

        foreach ($matching_atomic_types as $matching_atomic_type) {
            $atomic_types[] = $atomic_type->replaceTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                $matching_atomic_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth + 1
            );
        }

        return $atomic_types;
    }

    /**
     * @return list<Atomic>
     */
    private static function findMatchingAtomicTypesForTemplate(
        Union $input_type,
        Atomic $base_type,
        string $key,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer
    ) : array {
        $matching_atomic_types = [];

        foreach ($input_type->getAtomicTypes() as $input_key => $atomic_input_type) {
            if ($bracket_pos = strpos($input_key, '<')) {
                $input_key = substr($input_key, 0, $bracket_pos);
            }

            if ($input_key === $key) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof Atomic\TClosure && $base_type instanceof Atomic\TClosure) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof Atomic\TCallable
                && $base_type instanceof Atomic\TCallable
            ) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof Atomic\TClosure && $base_type instanceof Atomic\TCallable) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if (($atomic_input_type instanceof Atomic\TArray
                    || $atomic_input_type instanceof Atomic\TKeyedArray
                    || $atomic_input_type instanceof Atomic\TList)
                && $key === 'iterable'
            ) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if (strpos($input_key, $key . '&') === 0) {
                $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                continue;
            }

            if ($atomic_input_type instanceof Atomic\TLiteralClassString
                && $base_type instanceof Atomic\TClassString
                && $base_type->as_type
            ) {
                try {
                    $classlike_storage =
                        $codebase->classlike_storage_provider->get($atomic_input_type->value);

                    if (isset($classlike_storage->template_type_extends[$base_type->as_type->value])) {
                        $extends_list = $classlike_storage->template_type_extends[$base_type->as_type->value];

                        $new_generic_params = [];

                        foreach ($extends_list as $extends_key => $value) {
                            if (is_string($extends_key)) {
                                $new_generic_params[] = $value;
                            }
                        }

                        if ($new_generic_params) {
                            $atomic_input_type = new Atomic\TClassString(
                                $base_type->as_type->value,
                                new Atomic\TGenericObject(
                                    $base_type->as_type->value,
                                    $new_generic_params
                                )
                            );
                        }

                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }
                } catch (\InvalidArgumentException $e) {
                    // do nothing
                }
            }

            if ($base_type instanceof Atomic\TCallable) {
                $matching_atomic_type = CallableTypeComparator::getCallableFromAtomic(
                    $codebase,
                    $atomic_input_type,
                    null,
                    $statements_analyzer
                );

                if ($matching_atomic_type) {
                    $matching_atomic_types[$matching_atomic_type->getId()] = $matching_atomic_type;
                    continue;
                }
            }

            if ($atomic_input_type instanceof Atomic\TNamedObject
                && ($base_type instanceof Atomic\TNamedObject
                    || $base_type instanceof Atomic\TIterable)
            ) {
                if ($base_type instanceof Atomic\TIterable) {
                    if ($atomic_input_type->value === 'Traversable') {
                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }

                    $base_type = new Atomic\TGenericObject(
                        'Traversable',
                        $base_type->type_params
                    );
                }

                try {
                    $classlike_storage =
                        $codebase->classlike_storage_provider->get($atomic_input_type->value);

                    if ($atomic_input_type instanceof Atomic\TGenericObject
                        && isset($classlike_storage->template_type_extends[$base_type->value])
                    ) {
                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }

                    if (isset($classlike_storage->template_type_extends[$base_type->value])) {
                        $extends_list = $classlike_storage->template_type_extends[$base_type->value];

                        $new_generic_params = [];

                        foreach ($extends_list as $extends_key => $value) {
                            if (is_string($extends_key)) {
                                $new_generic_params[] = $value;
                            }
                        }

                        if (!$new_generic_params) {
                            $atomic_input_type = new Atomic\TNamedObject($atomic_input_type->value);
                        } else {
                            $atomic_input_type = new Atomic\TGenericObject(
                                $atomic_input_type->value,
                                $new_generic_params
                            );
                        }

                        $matching_atomic_types[$atomic_input_type->getId()] = $atomic_input_type;
                        continue;
                    }
                } catch (\InvalidArgumentException $e) {
                    // do nothing
                }
            }
        }

        return array_values($matching_atomic_types);
    }

    /**
     * @return list<Atomic>
     */
    private static function handleTemplateParamStandin(
        Atomic\TTemplateParam $atomic_type,
        string $key,
        ?Union $input_type,
        ?int $input_arg_offset,
        ?string $calling_class,
        ?string $calling_function,
        TemplateResult $template_result,
        ?Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer,
        bool $replace,
        bool $add_upper_bound,
        int $depth,
        bool $was_nullable,
        bool &$had_template
    ) : array {
        $template_type = $template_result->template_types
            [$atomic_type->param_name]
            [$atomic_type->defining_class]
            [0];

        if ($template_type->getId() === $key) {
            return array_values($template_type->getAtomicTypes());
        }

        $replacement_type = $template_type;

        $param_name_key = $atomic_type->param_name;

        if (strpos($key, '&')) {
            $param_name_key = $key;
        }

        $extra_types = [];

        if ($atomic_type->extra_types) {
            foreach ($atomic_type->extra_types as $extra_type) {
                $extra_type = self::replaceTemplateTypesWithStandins(
                    new \Psalm\Type\Union([$extra_type]),
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_type,
                    $input_arg_offset,
                    $calling_class,
                    $calling_function,
                    $replace,
                    $add_upper_bound,
                    $depth + 1
                );

                if ($extra_type->isSingle()) {
                    $extra_type = array_values($extra_type->getAtomicTypes())[0];

                    if ($extra_type instanceof Atomic\TNamedObject
                        || $extra_type instanceof Atomic\TTemplateParam
                        || $extra_type instanceof Atomic\TIterable
                        || $extra_type instanceof Atomic\TObjectWithProperties
                    ) {
                        $extra_types[$extra_type->getKey()] = $extra_type;
                    }
                }
            }
        }

        if ($replace) {
            $atomic_types = [];

            if ($replacement_type->hasMixed()
                && !$atomic_type->as->hasMixed()
            ) {
                foreach ($atomic_type->as->getAtomicTypes() as $as_atomic_type) {
                    $atomic_types[] = clone $as_atomic_type;
                }
            } else {
                if ($codebase) {
                    $replacement_type = TypeExpander::expandUnion(
                        $codebase,
                        $replacement_type,
                        $calling_class,
                        $calling_class,
                        null
                    );
                }

                if ($depth < 10) {
                    $replacement_type = self::replaceTemplateTypesWithStandins(
                        $replacement_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $input_type,
                        $input_arg_offset,
                        $calling_class,
                        $calling_function,
                        $replace,
                        $add_upper_bound,
                        $depth + 1
                    );
                }

                foreach ($replacement_type->getAtomicTypes() as $replacement_atomic_type) {
                    $replacements_found = false;

                    // @codingStandardsIgnoreStart
                    if ($replacement_atomic_type instanceof Atomic\TTemplateKeyOf
                        && isset($template_result->template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class][0])
                    ) {
                        $keyed_template = $template_result->template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class][0];

                        if ($keyed_template->isSingle()) {
                            $keyed_template = array_values($keyed_template->getAtomicTypes())[0];
                        }

                        if ($keyed_template instanceof Atomic\TKeyedArray
                            || $keyed_template instanceof Atomic\TArray
                            || $keyed_template instanceof Atomic\TList
                        ) {
                            if ($keyed_template instanceof Atomic\TKeyedArray) {
                                $key_type = $keyed_template->getGenericKeyType();
                            } elseif ($keyed_template instanceof Atomic\TList) {
                                $key_type = \Psalm\Type::getInt();
                            } else {
                                $key_type = $keyed_template->type_params[0];
                            }

                            $replacements_found = true;

                            foreach ($key_type->getAtomicTypes() as $key_type_atomic) {
                                $atomic_types[] = clone $key_type_atomic;
                            }

                            $template_result->upper_bounds[$atomic_type->param_name][$atomic_type->defining_class][0]
                                = clone $key_type;
                        }
                    }

                    if ($replacement_atomic_type instanceof Atomic\TTemplateParam
                        && $replacement_atomic_type->defining_class !== $calling_class
                        && $replacement_atomic_type->defining_class !== 'fn-' . $calling_function
                    ) {
                        foreach ($replacement_atomic_type->as->getAtomicTypes() as $nested_type_atomic) {
                            $replacements_found = true;
                            $atomic_types[] = clone $nested_type_atomic;
                        }
                    }
                    // @codingStandardsIgnoreEnd

                    if (!$replacements_found) {
                        $atomic_types[] = clone $replacement_atomic_type;
                    }

                    $had_template = true;
                }
            }

            $matching_input_keys = [];

            if ($codebase) {
                $atomic_type->as = TypeExpander::expandUnion(
                    $codebase,
                    $atomic_type->as,
                    $calling_class,
                    $calling_class,
                    null
                );
            }

            $atomic_type->as = self::replaceTemplateTypesWithStandins(
                $atomic_type->as,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth + 1
            );

            if ($input_type
                && (
                    $atomic_type->as->isMixed()
                    || !$codebase
                    || UnionTypeComparator::canBeContainedBy(
                        $codebase,
                        $input_type,
                        $atomic_type->as,
                        false,
                        false,
                        $matching_input_keys
                    )
                )
            ) {
                $generic_param = clone $input_type;

                if ($matching_input_keys) {
                    $generic_param_keys = \array_keys($generic_param->getAtomicTypes());

                    foreach ($generic_param_keys as $atomic_key) {
                        if (!isset($matching_input_keys[$atomic_key])) {
                            $generic_param->removeType($atomic_key);
                        }
                    }
                }

                if ($was_nullable && $generic_param->isNullable() && !$generic_param->isNull()) {
                    $generic_param->removeType('null');
                }

                if ($add_upper_bound) {
                    return array_values($generic_param->getAtomicTypes());
                }

                $generic_param->setFromDocblock();

                if (isset(
                    $template_result->upper_bounds[$param_name_key][$atomic_type->defining_class][0]
                )) {
                    $existing_generic_param = $template_result->upper_bounds
                        [$param_name_key]
                        [$atomic_type->defining_class];

                    $existing_depth = $existing_generic_param[1] ?? -1;
                    $existing_arg_offset = $existing_generic_param[2] ?? $input_arg_offset;

                    if ($existing_depth > $depth && $input_arg_offset === $existing_arg_offset) {
                        return $atomic_types ?: [$atomic_type];
                    }

                    if ($existing_depth === $depth || $input_arg_offset !== $existing_arg_offset) {
                        $generic_param = \Psalm\Type::combineUnionTypes(
                            $template_result->upper_bounds
                                [$param_name_key]
                                [$atomic_type->defining_class]
                                [0],
                            $generic_param,
                            $codebase
                        );
                    }
                }

                $template_result->upper_bounds[$param_name_key][$atomic_type->defining_class] = [
                    $generic_param,
                    $depth,
                    $input_arg_offset
                ];
            }

            foreach ($atomic_types as $atomic_type) {
                if ($atomic_type instanceof Atomic\TNamedObject
                    || $atomic_type instanceof Atomic\TTemplateParam
                    || $atomic_type instanceof Atomic\TIterable
                    || $atomic_type instanceof Atomic\TObjectWithProperties
                ) {
                    $atomic_type->extra_types = $extra_types;
                }
            }

            return $atomic_types;
        }

        if ($add_upper_bound && $input_type) {
            $matching_input_keys = [];

            if ($codebase
                && UnionTypeComparator::canBeContainedBy(
                    $codebase,
                    $input_type,
                    $replacement_type,
                    false,
                    false,
                    $matching_input_keys
                )
            ) {
                $generic_param = clone $input_type;

                if ($matching_input_keys) {
                    $generic_param_keys = \array_keys($generic_param->getAtomicTypes());

                    foreach ($generic_param_keys as $atomic_key) {
                        if (!isset($matching_input_keys[$atomic_key])) {
                            $generic_param->removeType($atomic_key);
                        }
                    }
                }

                if (isset($template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0])) {
                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0],
                        $generic_param
                    ) || !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $generic_param,
                        $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0]
                    )) {
                        $intersection_type = \Psalm\Type::intersectUnionTypes(
                            $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0],
                            $generic_param,
                            $codebase
                        );
                    } else {
                        $intersection_type = $generic_param;
                    }

                    if ($intersection_type) {
                        $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0]
                            = $intersection_type;
                    } else {
                        $template_result->lower_bounds_unintersectable_types[]
                            = $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0];
                        $template_result->lower_bounds_unintersectable_types[] = $generic_param;

                        $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0]
                            = \Psalm\Type::getMixed();
                    }
                } else {
                    $template_result->lower_bounds[$param_name_key][$atomic_type->defining_class][0]
                        = $generic_param;
                }
            }
        }

        return [$atomic_type];
    }

    /**
     * @return non-empty-list<Atomic\TClassString>
     */
    public static function handleTemplateParamClassStandin(
        Atomic\TTemplateParamClass $atomic_type,
        ?Union $input_type,
        ?int $input_arg_offset,
        TemplateResult $template_result,
        int $depth,
        bool $was_single
    ) : array {
        $class_string = new Atomic\TClassString($atomic_type->as, $atomic_type->as_type);

        $atomic_types = [];

        $atomic_types[] = $class_string;

        if ($input_type) {
            $valid_input_atomic_types = [];

            foreach ($input_type->getAtomicTypes() as $input_atomic_type) {
                if ($input_atomic_type instanceof Atomic\TLiteralClassString) {
                    $valid_input_atomic_types[] = new Atomic\TNamedObject(
                        $input_atomic_type->value
                    );
                } elseif ($input_atomic_type instanceof Atomic\TTemplateParamClass) {
                    $valid_input_atomic_types[] = new Atomic\TTemplateParam(
                        $input_atomic_type->param_name,
                        $input_atomic_type->as_type
                            ? new Union([$input_atomic_type->as_type])
                            : ($input_atomic_type->as === 'object'
                                ? \Psalm\Type::getObject()
                                : \Psalm\Type::getMixed()),
                        $input_atomic_type->defining_class
                    );
                } elseif ($input_atomic_type instanceof Atomic\TClassString) {
                    if ($input_atomic_type->as_type) {
                        $valid_input_atomic_types[] = clone $input_atomic_type->as_type;
                    } elseif ($input_atomic_type->as !== 'object') {
                        $valid_input_atomic_types[] = new Atomic\TNamedObject(
                            $input_atomic_type->as
                        );
                    } else {
                        $valid_input_atomic_types[] = new Atomic\TObject();
                    }
                } elseif ($input_atomic_type instanceof Atomic\TDependentGetClass) {
                    $valid_input_atomic_types[] = new Atomic\TObject();
                }
            }

            $generic_param = null;

            if ($valid_input_atomic_types) {
                $generic_param = new Union($valid_input_atomic_types);
                $generic_param->setFromDocblock();
            } elseif ($was_single) {
                $generic_param = \Psalm\Type::getMixed();
            }

            if ($generic_param) {
                if (isset($template_result->upper_bounds[$atomic_type->param_name][$atomic_type->defining_class])) {
                    $template_result->upper_bounds[$atomic_type->param_name][$atomic_type->defining_class] = [
                        \Psalm\Type::combineUnionTypes(
                            $generic_param,
                            $template_result->upper_bounds[$atomic_type->param_name][$atomic_type->defining_class][0]
                        ),
                        $depth
                    ];
                } else {
                    $template_result->upper_bounds[$atomic_type->param_name][$atomic_type->defining_class] = [
                        $generic_param,
                        $depth,
                        $input_arg_offset
                    ];
                }
            }
        }

        return $atomic_types;
    }

    /**
     * @param  array<string, array<string, array{Union, 1?:int}>>  $template_types
     *
     * @return array{Union, 1?:int}|null
     */
    public static function getRootTemplateType(
        array $template_types,
        string $param_name,
        string $defining_class,
        array $visited_classes = []
    ) : ?array {
        if (isset($visited_classes[$defining_class])) {
            return null;
        }

        if (isset($template_types[$param_name][$defining_class])) {
            $mapped_type = $template_types[$param_name][$defining_class][0];

            $mapped_type_atomic_types = array_values($mapped_type->getAtomicTypes());

            if (count($mapped_type_atomic_types) > 1
                || !$mapped_type_atomic_types[0] instanceof Atomic\TTemplateParam
            ) {
                return $template_types[$param_name][$defining_class];
            }

            $first_template = $mapped_type_atomic_types[0];

            return self::getRootTemplateType(
                $template_types,
                $first_template->param_name,
                $first_template->defining_class,
                $visited_classes + [$defining_class => true]
            ) ?? $template_types[$param_name][$defining_class];
        }

        return null;
    }

    /**
     * @param Atomic\TGenericObject|Atomic\TIterable $input_type_part
     * @param Atomic\TGenericObject|Atomic\TIterable $container_type_part
     * @return list<Union>
     */
    public static function getMappedGenericTypeParams(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        ?array &$container_type_params_covariant = null
    ) : array {
        $input_type_params = $input_type_part->type_params;

        try {
            $input_class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);
            $container_class_storage = $codebase->classlike_storage_provider->get($container_type_part->value);
            $container_type_params_covariant = $container_class_storage->template_covariants;
        } catch (\Throwable $e) {
            $input_class_storage = null;
        }

        if ($input_type_part->value !== $container_type_part->value
            && $input_class_storage
        ) {
            $input_template_types = $input_class_storage->template_types;
            $i = 0;

            $replacement_templates = [];

            if ($input_template_types
                && (!$input_type_part instanceof Atomic\TGenericObject || !$input_type_part->remapped_params)
                && (!$container_type_part instanceof Atomic\TGenericObject || !$container_type_part->remapped_params)
            ) {
                foreach ($input_template_types as $template_name => $_) {
                    if (!isset($input_type_params[$i])) {
                        break;
                    }

                    $replacement_templates[$template_name][$input_type_part->value] = [$input_type_params[$i]];

                    $i++;
                }
            }

            $template_extends = $input_class_storage->template_type_extends;

            if (isset($template_extends[$container_type_part->value])) {
                $params = $template_extends[$container_type_part->value];

                $new_input_params = [];

                foreach ($params as $key => $extended_input_param_type) {
                    if (is_string($key)) {
                        $new_input_param = null;

                        foreach ($extended_input_param_type->getAtomicTypes() as $et) {
                            if ($et instanceof Atomic\TTemplateParam) {
                                $ets = \Psalm\Internal\Codebase\Methods::getExtendedTemplatedTypes(
                                    $et,
                                    $template_extends
                                );
                            } else {
                                $ets = [];
                            }

                            if ($ets
                                && $ets[0] instanceof Atomic\TTemplateParam
                                && isset(
                                    $input_class_storage->template_types
                                        [$ets[0]->param_name]
                                        [$ets[0]->defining_class]
                                )
                            ) {
                                $old_params_offset = (int) \array_search(
                                    $ets[0]->param_name,
                                    \array_keys($input_class_storage->template_types)
                                );

                                if (!isset($input_type_params[$old_params_offset])) {
                                    $candidate_param_type = \Psalm\Type::getMixed();
                                } else {
                                    $candidate_param_type = $input_type_params[$old_params_offset];
                                }
                            } else {
                                $candidate_param_type = new Union([clone $et]);
                            }

                            $candidate_param_type->from_template_default = true;

                            if (!$new_input_param) {
                                $new_input_param = $candidate_param_type;
                            } else {
                                $new_input_param = \Psalm\Type::combineUnionTypes(
                                    $new_input_param,
                                    $candidate_param_type
                                );
                            }
                        }

                        $new_input_param = clone $new_input_param;
                        $new_input_param->replaceTemplateTypesWithArgTypes(
                            new TemplateResult([], $replacement_templates),
                            $codebase
                        );

                        $new_input_params[] = $new_input_param;
                    }
                }

                $input_type_params = $new_input_params;
            }
        }

        return $input_type_params;
    }
}
