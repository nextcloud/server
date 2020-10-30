<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Codebase;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TScalarClassConstant;

use function array_merge;
use function array_search;
use function array_keys;

class ClassTemplateParamCollector
{
    /**
     * @param lowercase-string $method_name
     * @return array<string, array<string, array{Type\Union, 1?:int}>>|null
     */
    public static function collect(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        ClassLikeStorage $static_class_storage,
        ?string $method_name = null,
        ?Type\Atomic $lhs_type_part = null,
        ?string $lhs_var_id = null
    ): ?array {
        $static_fq_class_name = $static_class_storage->name;

        $non_trait_class_storage = $class_storage->is_trait
            ? $static_class_storage
            : $class_storage;

        $template_types = $class_storage->template_types;

        $candidate_class_storages = [$class_storage];

        if ($static_class_storage->template_type_extends
            && $method_name
            && !empty($non_trait_class_storage->overridden_method_ids[$method_name])
            && isset($class_storage->methods[$method_name])
            && (!isset($non_trait_class_storage->methods[$method_name]->return_type)
                || $class_storage->methods[$method_name]->inherited_return_type)
        ) {
            foreach ($non_trait_class_storage->overridden_method_ids[$method_name] as $overridden_method_id) {
                $overridden_storage = $codebase->methods->getStorage($overridden_method_id);

                if (!$overridden_storage->return_type) {
                    continue;
                }

                if ($overridden_storage->return_type->isNull()) {
                    continue;
                }

                $fq_overridden_class = $overridden_method_id->fq_class_name;

                $overridden_class_storage = $codebase->classlike_storage_provider->get($fq_overridden_class);

                $overridden_template_types = $overridden_class_storage->template_types;

                if (!$template_types) {
                    $template_types = $overridden_template_types;
                } elseif ($overridden_template_types) {
                    foreach ($overridden_template_types as $template_name => $template_map) {
                        if (isset($template_types[$template_name])) {
                            $template_types[$template_name] = array_merge(
                                $template_types[$template_name],
                                $template_map
                            );
                        } else {
                            $template_types[$template_name] = $template_map;
                        }
                    }
                }

                $candidate_class_storages[] = $overridden_class_storage;
            }
        }

        if (!$template_types) {
            return null;
        }

        $class_template_params = [];
        $e = $static_class_storage->template_type_extends;

        if ($lhs_type_part instanceof TGenericObject) {
            if ($class_storage === $static_class_storage && $static_class_storage->template_types) {
                $i = 0;

                foreach ($static_class_storage->template_types as $type_name => $_) {
                    if (isset($lhs_type_part->type_params[$i])) {
                        if ($lhs_var_id !== '$this' || $static_fq_class_name !== $static_class_storage->name) {
                            $class_template_params[$type_name][$static_class_storage->name] = [
                                $lhs_type_part->type_params[$i]
                            ];
                        }
                    }

                    $i++;
                }
            }

            foreach ($template_types as $type_name => $_) {
                if (isset($class_template_params[$type_name])) {
                    continue;
                }

                if ($class_storage !== $static_class_storage
                    && isset($e[$class_storage->name][$type_name])
                ) {
                    $input_type_extends = $e[$class_storage->name][$type_name];

                    $output_type_extends = null;

                    foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
                        if ($type_extends_atomic instanceof Type\Atomic\TTemplateParam) {
                            if (isset($static_class_storage->template_types[$type_extends_atomic->param_name])) {
                                $mapped_offset = array_search(
                                    $type_extends_atomic->param_name,
                                    array_keys($static_class_storage->template_types)
                                );

                                if (isset($lhs_type_part->type_params[(int) $mapped_offset])) {
                                    $candidate_type = $lhs_type_part->type_params[(int) $mapped_offset];

                                    if (!$output_type_extends) {
                                        $output_type_extends = $candidate_type;
                                    } else {
                                        $output_type_extends = Type::combineUnionTypes(
                                            $candidate_type,
                                            $output_type_extends
                                        );
                                    }
                                }
                            } elseif (isset(
                                $static_class_storage
                                    ->template_type_extends
                                        [$type_extends_atomic->defining_class]
                                        [$type_extends_atomic->param_name]
                            )) {
                                $mapped_offset = array_search(
                                    $type_extends_atomic->param_name,
                                    array_keys($static_class_storage
                                    ->template_type_extends
                                        [$type_extends_atomic->defining_class])
                                );

                                if (isset($lhs_type_part->type_params[(int) $mapped_offset])) {
                                    $candidate_type = $lhs_type_part->type_params[(int) $mapped_offset];

                                    if (!$output_type_extends) {
                                        $output_type_extends = $candidate_type;
                                    } else {
                                        $output_type_extends = Type::combineUnionTypes(
                                            $candidate_type,
                                            $output_type_extends
                                        );
                                    }
                                }
                            }
                        } else {
                            if (!$output_type_extends) {
                                $output_type_extends = new Type\Union([$type_extends_atomic]);
                            } else {
                                $output_type_extends = Type::combineUnionTypes(
                                    new Type\Union([$type_extends_atomic]),
                                    $output_type_extends
                                );
                            }
                        }
                    }

                    if ($lhs_var_id !== '$this' || $static_fq_class_name !== $class_storage->name) {
                        $class_template_params[$type_name][$class_storage->name] = [
                            $output_type_extends ?: Type::getMixed()
                        ];
                    }
                }

                if (($lhs_var_id !== '$this' || $static_fq_class_name !== $class_storage->name)
                    && !isset($class_template_params[$type_name])
                ) {
                    $class_template_params[$type_name] = [
                        $class_storage->name => [Type::getMixed()]
                    ];
                }
            }
        }

        foreach ($template_types as $type_name => $type_map) {
            foreach ($type_map as [$type]) {
                foreach ($candidate_class_storages as $candidate_class_storage) {
                    if ($candidate_class_storage !== $static_class_storage
                        && isset($e[$candidate_class_storage->name][$type_name])
                        && !isset($class_template_params[$type_name][$candidate_class_storage->name])
                    ) {
                        $class_template_params[$type_name][$candidate_class_storage->name] = [
                            new Type\Union(
                                self::expandType(
                                    $codebase,
                                    $e[$candidate_class_storage->name][$type_name],
                                    $e,
                                    $static_class_storage->name,
                                    $static_class_storage->template_types
                                )
                            )
                        ];
                    }
                }

                if ($lhs_var_id !== '$this') {
                    if (!isset($class_template_params[$type_name])) {
                        $class_template_params[$type_name][$class_storage->name] = [$type];
                    }
                }
            }
        }

        return $class_template_params;
    }

    /**
     * @param array<string, array<int|string, Type\Union>> $e
     * @return non-empty-list<Type\Atomic>
     */
    private static function expandType(
        Codebase $codebase,
        Type\Union $input_type_extends,
        array $e,
        string $static_fq_class_name,
        ?array $static_template_types
    ) : array {
        $output_type_extends = [];

        foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
            if ($type_extends_atomic instanceof Type\Atomic\TTemplateParam
                && ($static_fq_class_name !== $type_extends_atomic->defining_class
                    || !isset($static_template_types[$type_extends_atomic->param_name]))
                && isset($e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name])
            ) {
                $output_type_extends = array_merge(
                    $output_type_extends,
                    self::expandType(
                        $codebase,
                        $e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name],
                        $e,
                        $static_fq_class_name,
                        $static_template_types
                    )
                );
            } elseif ($type_extends_atomic instanceof TScalarClassConstant) {
                $expanded = TypeExpander::expandAtomic(
                    $codebase,
                    $type_extends_atomic,
                    $type_extends_atomic->fq_classlike_name,
                    $type_extends_atomic->fq_classlike_name,
                    null,
                    true,
                    true
                );

                if ($expanded instanceof Atomic) {
                    $output_type_extends[] = $expanded;
                } else {
                    foreach ($expanded as $type) {
                        $output_type_extends[] = $type;
                    }
                }
            } else {
                $output_type_extends[] = $type_extends_atomic;
            }
        }

        return $output_type_extends;
    }
}
