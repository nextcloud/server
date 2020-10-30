<?php
namespace Psalm\Internal\Type;

use function array_filter;
use function count;
use function get_class;
use function is_string;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use function strpos;
use function substr;
use Psalm\Issue\InvalidDocblock;
use function array_intersect_key;
use function array_merge;

class AssertionReconciler extends \Psalm\Type\Reconciler
{
    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string[]            $suppressed_issues
     * @param   array<string, array<string, array{Type\Union}>> $template_type_map
     * @param-out   0|1|2   $failed_reconciliation
     */
    public static function reconcile(
        string $assertion,
        ?Union $existing_var_type,
        ?string $key,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        array $template_type_map,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        ?int &$failed_reconciliation = 0,
        bool $negated = false
    ) : Union {
        $codebase = $statements_analyzer->getCodebase();

        $is_strict_equality = false;
        $is_loose_equality = false;
        $is_equality = false;
        $is_negation = false;
        $failed_reconciliation = 0;

        if ($assertion[0] === '!') {
            $assertion = substr($assertion, 1);
            $is_negation = true;
        }

        if ($assertion[0] === '=') {
            $assertion = substr($assertion, 1);
            $is_strict_equality = true;
            $is_equality = true;
        }

        if ($assertion[0] === '~') {
            $assertion = substr($assertion, 1);
            $is_loose_equality = true;
            $is_equality = true;
        }

        if ($assertion[0] === '>') {
            $assertion = 'falsy';
            $is_negation = true;
        }

        if ($existing_var_type === null
            && is_string($key)
            && VariableFetchAnalyzer::isSuperGlobal($key)
        ) {
            $existing_var_type = VariableFetchAnalyzer::getGlobalType($key);
        }

        if ($existing_var_type === null) {
            if (($assertion === 'isset' && !$is_negation)
                || ($assertion === 'empty' && $is_negation)
            ) {
                return Type::getMixed($inside_loop);
            }

            if ($assertion === 'array-key-exists'
                || $assertion === 'non-empty-countable'
                || strpos($assertion, 'has-at-least-') === 0
                || strpos($assertion, 'has-exactly-') === 0
            ) {
                return Type::getMixed();
            }

            if (!$is_negation && $assertion !== 'falsy' && $assertion !== 'empty') {
                if ($is_equality) {
                    $bracket_pos = strpos($assertion, '(');

                    if ($bracket_pos) {
                        $assertion = substr($assertion, 0, $bracket_pos);
                    }
                }

                try {
                    return Type::parseString($assertion, null, $template_type_map);
                } catch (\Exception $e) {
                    return Type::getMixed();
                }
            }

            return Type::getMixed();
        }

        $old_var_type_string = $existing_var_type->getId();

        if ($is_negation) {
            return NegatedAssertionReconciler::reconcile(
                $statements_analyzer,
                $assertion,
                $is_strict_equality,
                $is_loose_equality,
                $existing_var_type,
                $template_type_map,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        $simply_reconciled_type = SimpleAssertionReconciler::reconcile(
            $assertion,
            $codebase,
            $existing_var_type,
            $key,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
            $is_equality,
            $is_strict_equality,
            $inside_loop
        );

        if ($simply_reconciled_type) {
            return $simply_reconciled_type;
        }

        if (substr($assertion, 0, 4) === 'isa-') {
            $assertion = substr($assertion, 4);

            $allow_string_comparison = false;

            if (substr($assertion, 0, 7) === 'string-') {
                $assertion = substr($assertion, 7);
                $allow_string_comparison = true;
            }

            if ($existing_var_type->hasMixed()) {
                $type = new Type\Union([
                    new Type\Atomic\TNamedObject($assertion),
                ]);

                if ($allow_string_comparison) {
                    $type->addType(
                        new Type\Atomic\TClassString(
                            $assertion,
                            new Type\Atomic\TNamedObject($assertion)
                        )
                    );
                }

                return $type;
            }

            $existing_has_object = $existing_var_type->hasObjectType();
            $existing_has_string = $existing_var_type->hasString();

            if ($existing_has_object && !$existing_has_string) {
                $new_type = Type::parseString($assertion, null, $template_type_map);
            } elseif ($existing_has_string && !$existing_has_object) {
                if (!$allow_string_comparison && $code_location) {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            'Cannot allow string comparison to object for ' . $key,
                            $code_location,
                            null
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    $new_type = Type::getMixed();
                } else {
                    $new_type_has_interface_string = $codebase->interfaceExists($assertion);

                    $old_type_has_interface_string = false;

                    foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                        if ($existing_type_part instanceof TClassString
                            && $existing_type_part->as_type
                            && $codebase->interfaceExists($existing_type_part->as_type->value)
                        ) {
                            $old_type_has_interface_string = true;
                            break;
                        }
                    }

                    if (isset($template_type_map[$assertion])) {
                        $new_type = Type::parseString(
                            'class-string<' . $assertion . '>',
                            null,
                            $template_type_map
                        );
                    } else {
                        $new_type = Type::getClassString($assertion);
                    }

                    if ((
                        $new_type_has_interface_string
                            && !UnionTypeComparator::isContainedBy(
                                $codebase,
                                $existing_var_type,
                                $new_type
                            )
                    )
                        || (
                            $old_type_has_interface_string
                            && !UnionTypeComparator::isContainedBy(
                                $codebase,
                                $new_type,
                                $existing_var_type
                            )
                        )
                    ) {
                        $new_type_part = Atomic::create($assertion, null, $template_type_map);

                        $acceptable_atomic_types = [];

                        foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                            if (!$new_type_part instanceof TNamedObject
                                || !$existing_var_type_part instanceof TClassString
                            ) {
                                $acceptable_atomic_types = [];

                                break;
                            }

                            if (!$existing_var_type_part->as_type instanceof TNamedObject) {
                                $acceptable_atomic_types = [];

                                break;
                            }

                            $existing_var_type_part = $existing_var_type_part->as_type;

                            if (AtomicTypeComparator::isContainedBy(
                                $codebase,
                                $existing_var_type_part,
                                $new_type_part
                            )) {
                                $acceptable_atomic_types[] = clone $existing_var_type_part;
                                continue;
                            }

                            if ($codebase->classExists($existing_var_type_part->value)
                                || $codebase->interfaceExists($existing_var_type_part->value)
                            ) {
                                $existing_var_type_part = clone $existing_var_type_part;
                                $existing_var_type_part->addIntersectionType($new_type_part);
                                $acceptable_atomic_types[] = $existing_var_type_part;
                            }
                        }

                        if (count($acceptable_atomic_types) === 1) {
                            return new Type\Union([
                                new TClassString('object', $acceptable_atomic_types[0]),
                            ]);
                        }
                    }
                }
            } else {
                $new_type = Type::getMixed();
            }
        } elseif (substr($assertion, 0, 9) === 'getclass-') {
            $assertion = substr($assertion, 9);
            $new_type = Type::parseString($assertion, null, $template_type_map);
        } else {
            $bracket_pos = strpos($assertion, '(');

            if ($bracket_pos) {
                return self::handleLiteralEquality(
                    $assertion,
                    $bracket_pos,
                    $is_loose_equality,
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $new_type = Type::parseString($assertion, null, $template_type_map);
        }

        if ($existing_var_type->hasMixed()) {
            if ($is_loose_equality
                && $new_type->hasScalarType()
            ) {
                return $existing_var_type;
            }

            return $new_type;
        }

        return self::refine(
            $statements_analyzer,
            $assertion,
            $new_type,
            $existing_var_type,
            $template_type_map,
            $key,
            $negated,
            $code_location,
            $is_equality,
            $is_loose_equality,
            $suppressed_issues,
            $failed_reconciliation
        );
    }

    /**
     * @param 0|1|2         $failed_reconciliation
     * @param   string[]    $suppressed_issues
     * @param   array<string, array<string, array{Type\Union}>> $template_type_map
     * @param-out   0|1|2   $failed_reconciliation
     */
    private static function refine(
        StatementsAnalyzer $statements_analyzer,
        string $assertion,
        Union $new_type,
        Union $existing_var_type,
        array $template_type_map,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        bool $is_equality,
        bool $is_loose_equality,
        array $suppressed_issues,
        int &$failed_reconciliation
    ) : Union {
        $codebase = $statements_analyzer->getCodebase();

        $old_var_type_string = $existing_var_type->getId();

        $new_type_has_interface = false;

        if ($new_type->hasObjectType()) {
            foreach ($new_type->getAtomicTypes() as $new_type_part) {
                if ($new_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($new_type_part->value)
                ) {
                    $new_type_has_interface = true;
                    break;
                }
            }
        }

        $old_type_has_interface = false;

        if ($existing_var_type->hasObjectType()) {
            foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                if ($existing_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($existing_type_part->value)
                ) {
                    $old_type_has_interface = true;
                    break;
                }
            }
        }

        try {
            if (strpos($assertion, '<') || strpos($assertion, '[') || strpos($assertion, '{')) {
                $new_type_union = Type::parseString($assertion);

                $new_type_part = \array_values($new_type_union->getAtomicTypes())[0];
            } else {
                $new_type_part = Atomic::create($assertion, null, $template_type_map);
            }
        } catch (\Psalm\Exception\TypeParseTreeException $e) {
            $new_type_part = new TMixed();

            if ($code_location) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        $assertion . ' cannot be used in an assertion',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        }

        if ($new_type_part instanceof Type\Atomic\TTemplateParam
            && $new_type_part->as->isSingle()
        ) {
            $new_as_atomic = \array_values($new_type_part->as->getAtomicTypes())[0];

            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TNamedObject
                    || $existing_var_type_part instanceof TTemplateParam
                ) {
                    $new_type_part->addIntersectionType($existing_var_type_part);
                    $acceptable_atomic_types[] = clone $existing_var_type_part;
                } else {
                    if (AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_as_atomic
                    )) {
                        $acceptable_atomic_types[] = clone $existing_var_type_part;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                $new_type_part->as = new Type\Union($acceptable_atomic_types);

                return new Type\Union([$new_type_part]);
            }
        }

        if ($new_type_part instanceof Type\Atomic\TKeyedArray) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof Type\Atomic\TKeyedArray) {
                    if (!array_intersect_key(
                        $existing_var_type_part->properties,
                        $new_type_part->properties
                    )) {
                        $existing_var_type_part = clone $existing_var_type_part;
                        $existing_var_type_part->properties = array_merge(
                            $existing_var_type_part->properties,
                            $new_type_part->properties
                        );

                        $acceptable_atomic_types[] = $existing_var_type_part;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                return new Type\Union($acceptable_atomic_types);
            }
        }

        if ($new_type_part instanceof TNamedObject
            && ((
                $new_type_has_interface
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $existing_var_type,
                        $new_type
                    )
            )
                || (
                    $old_type_has_interface
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $new_type,
                        $existing_var_type
                    )
                ))
        ) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_type_part
                )) {
                    $acceptable_atomic_types[] = clone $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject
                    && ($codebase->classExists($existing_var_type_part->value)
                        || $codebase->interfaceExists($existing_var_type_part->value))
                ) {
                    $existing_var_type_part = clone $existing_var_type_part;
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }

                if ($existing_var_type_part instanceof TTemplateParam) {
                    $existing_var_type_part = clone $existing_var_type_part;
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Type\Union($acceptable_atomic_types);
            }
        } elseif (!$new_type->hasMixed()) {
            $has_match = true;

            if ($key
                && $code_location
                && $new_type->getId() === $existing_var_type->getId()
                && !$is_equality
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString())))
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $any_scalar_type_match_found = false;

            if ($code_location
                && $key
                && !$is_equality
                && $new_type_part instanceof TNamedObject
                && !$new_type_has_interface
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString())))
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type,
                    $new_type
                )
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $new_type = self::filterTypeWithAnother(
                $codebase,
                $existing_var_type,
                $new_type,
                $template_type_map,
                $has_match,
                $any_scalar_type_match_found
            );

            if ($code_location
                && !$has_match
                && (!$is_loose_equality || !$any_scalar_type_match_found)
            ) {
                if ($assertion === 'null') {
                    if ($existing_var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId() . ' null'
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainNull(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type
                                    . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId()
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString()))
                ) {
                    if ($existing_var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type->getId() . ' does not contain ' . $new_type->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type->getId()
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type->getId()
                                    . ' does not contain ' . $new_type->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type->getId()
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                }

                $failed_reconciliation = 2;
            }
        }

        return $new_type;
    }



    /**
     * @param array<string, array<string, array{0:Type\Union, 1?: int}>> $template_type_map
     */
    private static function filterTypeWithAnother(
        Codebase $codebase,
        Type\Union $existing_type,
        Type\Union $new_type,
        array $template_type_map,
        bool &$has_match = false,
        bool &$any_scalar_type_match_found = false
    ) : Type\Union {
        $matching_atomic_types = [];

        $has_cloned_type = false;

        foreach ($new_type->getAtomicTypes() as $new_type_part) {
            $has_local_match = false;

            foreach ($existing_type->getAtomicTypes() as $key => $existing_type_part) {
                // special workaround because PHP allows floats to contain ints, but we don’t want this
                // behaviour here
                if ($existing_type_part instanceof Type\Atomic\TFloat
                    && $new_type_part instanceof Type\Atomic\TInt
                ) {
                    $any_scalar_type_match_found = true;
                    continue;
                }

                $atomic_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                if ($existing_type_part instanceof TNamedObject) {
                    $existing_type_part->was_static = false;
                }

                $atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $new_type_part,
                    $existing_type_part,
                    true,
                    false,
                    $atomic_comparison_results
                );

                if ($atomic_contained_by) {
                    $has_local_match = true;

                    if ($atomic_comparison_results->type_coerced
                        && get_class($new_type_part) === Type\Atomic\TNamedObject::class
                        && $existing_type_part instanceof Type\Atomic\TGenericObject
                    ) {
                        // this is a hack - it's not actually rigorous, as the params may be different
                        $matching_atomic_types[] = new Type\Atomic\TGenericObject(
                            $new_type_part->value,
                            $existing_type_part->type_params
                        );
                    }
                } elseif (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_type_part,
                    $new_type_part,
                    true,
                    false,
                    null
                )) {
                    $has_local_match = true;
                    $matching_atomic_types[] = $existing_type_part;
                }

                if ($new_type_part instanceof Type\Atomic\TKeyedArray
                    && $existing_type_part instanceof Type\Atomic\TList
                ) {
                    $new_type_key = $new_type_part->getGenericKeyType();
                    $new_type_value = $new_type_part->getGenericValueType();

                    if (!$new_type_key->hasString()) {
                        $has_param_match = false;

                        $new_type_value = self::filterTypeWithAnother(
                            $codebase,
                            $existing_type_part->type_param,
                            $new_type_value,
                            $template_type_map,
                            $has_param_match,
                            $any_scalar_type_match_found
                        );

                        $hybrid_type_part = new Type\Atomic\TKeyedArray($new_type_part->properties);
                        $hybrid_type_part->previous_key_type = Type::getInt();
                        $hybrid_type_part->previous_value_type = $new_type_value;
                        $hybrid_type_part->is_list = true;

                        if (!$has_cloned_type) {
                            $new_type = clone $new_type;
                            $has_cloned_type = true;
                        }

                        $has_local_match = true;

                        $new_type->removeType($key);
                        $new_type->addType($hybrid_type_part);

                        continue;
                    }
                }

                if ($new_type_part instanceof Type\Atomic\TTemplateParam
                    && $existing_type_part instanceof Type\Atomic\TTemplateParam
                    && $new_type_part->param_name !== $existing_type_part->param_name
                    && $new_type_part->as->hasObject()
                    && $existing_type_part->as->hasObject()
                ) {
                    $new_type_part->extra_types[$existing_type_part->getKey()] = $existing_type_part;
                    $matching_atomic_types[] = $new_type_part;
                    $has_local_match = true;

                    continue;
                }

                if ($has_local_match
                    && $new_type_part instanceof Type\Atomic\TNamedObject
                    && $existing_type_part instanceof Type\Atomic\TTemplateParam
                    && $existing_type_part->as->hasObjectType()
                ) {
                    $existing_type_part = clone $existing_type_part;
                    $existing_type_part->as = self::filterTypeWithAnother(
                        $codebase,
                        $existing_type_part->as,
                        new Type\Union([$new_type_part]),
                        $template_type_map
                    );

                    $matching_atomic_types[] = $existing_type_part;
                    $has_local_match = true;

                    continue;
                }

                if (($new_type_part instanceof Type\Atomic\TGenericObject
                        || $new_type_part instanceof Type\Atomic\TArray
                        || $new_type_part instanceof Type\Atomic\TIterable)
                    && ($existing_type_part instanceof Type\Atomic\TGenericObject
                        || $existing_type_part instanceof Type\Atomic\TArray
                        || $existing_type_part instanceof Type\Atomic\TIterable)
                    && count($new_type_part->type_params) === count($existing_type_part->type_params)
                ) {
                    $has_any_param_match = false;

                    foreach ($new_type_part->type_params as $i => $new_param) {
                        $existing_param = $existing_type_part->type_params[$i];

                        $has_param_match = true;

                        $new_param = self::filterTypeWithAnother(
                            $codebase,
                            $existing_param,
                            $new_param,
                            $template_type_map,
                            $has_param_match,
                            $any_scalar_type_match_found
                        );

                        if ($template_type_map) {
                            $new_param->replaceTemplateTypesWithArgTypes(
                                new TemplateResult([], $template_type_map),
                                $codebase
                            );
                        }

                        $existing_type->bustCache();

                        if ($has_param_match
                            && $existing_type_part->type_params[$i]->getId() !== $new_param->getId()
                        ) {
                            $existing_type_part->type_params[$i] = $new_param;

                            if (!$has_local_match) {
                                $has_any_param_match = true;
                            }
                        }
                    }

                    if ($has_any_param_match) {
                        $has_local_match = true;
                        $matching_atomic_types[] = $existing_type_part;
                        $atomic_comparison_results->type_coerced = true;
                    }
                }

                if (($new_type_part instanceof Type\Atomic\TArray
                        || $new_type_part instanceof Type\Atomic\TIterable)
                    && $existing_type_part instanceof Type\Atomic\TList
                ) {
                    $has_any_param_match = false;

                    $new_param = $new_type_part->type_params[1];
                    $existing_param = $existing_type_part->type_param;

                    $has_param_match = true;

                    $new_param = self::filterTypeWithAnother(
                        $codebase,
                        $existing_param,
                        $new_param,
                        $template_type_map,
                        $has_param_match,
                        $any_scalar_type_match_found
                    );

                    if ($template_type_map) {
                        $new_param->replaceTemplateTypesWithArgTypes(
                            new TemplateResult([], $template_type_map),
                            $codebase
                        );
                    }

                    $existing_type->bustCache();

                    if ($has_param_match
                        && $existing_type_part->type_param->getId() !== $new_param->getId()
                    ) {
                        $existing_type_part->type_param = $new_param;

                        if (!$has_local_match) {
                            $has_any_param_match = true;
                        }
                    }

                    if ($has_any_param_match) {
                        $has_local_match = true;
                        $matching_atomic_types[] = $existing_type_part;
                        $atomic_comparison_results->type_coerced = true;
                    }
                }

                if ($atomic_contained_by || $atomic_comparison_results->type_coerced) {
                    if ($atomic_contained_by
                        && $existing_type_part instanceof TNamedObject
                        && $new_type_part instanceof TNamedObject
                        && $existing_type_part->extra_types
                        && !$codebase->classExists($existing_type_part->value)
                        && !$codebase->classExists($new_type_part->value)
                        && !array_filter(
                            $existing_type_part->extra_types,
                            function ($extra_type) use ($codebase): bool {
                                return $extra_type instanceof TNamedObject
                                    && $codebase->classExists($extra_type->value);
                            }
                        )
                    ) {
                        if (!$has_cloned_type) {
                            $new_type = clone $new_type;
                            $has_cloned_type = true;
                        }

                        $new_type->removeType($key);
                        $new_type->addType($existing_type_part);
                        $new_type->from_docblock = $existing_type_part->from_docblock;
                    }

                    continue;
                }

                if ($atomic_comparison_results->scalar_type_match_found) {
                    $any_scalar_type_match_found = true;
                }
            }

            if (!$has_local_match) {
                $has_match = false;
                break;
            }
        }

        if ($matching_atomic_types) {
            return new Type\Union($matching_atomic_types);
        }

        return $new_type;
    }

    /**
     * @param  string[]   $suppressed_issues
     */
    private static function handleLiteralEquality(
        string $assertion,
        int $bracket_pos,
        bool $is_loose_equality,
        Type\Union $existing_var_type,
        string $old_var_type_string,
        ?string $var_id,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues
    ) : Type\Union {
        $value = substr($assertion, $bracket_pos + 1, -1);

        $scalar_type = substr($assertion, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($scalar_type === 'int') {
            $value = (int) $value;

            if ($existing_var_type->hasMixed()
                || $existing_var_type->hasScalar()
                || $existing_var_type->hasNumeric()
                || $existing_var_type->hasArrayKey()
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                return new Type\Union([new Type\Atomic\TLiteralInt($value)]);
            }

            $has_int = false;

            foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
                if ($existing_var_atomic_type instanceof TInt) {
                    $has_int = true;
                } elseif ($existing_var_atomic_type instanceof TTemplateParam) {
                    if ($existing_var_atomic_type->as->hasMixed()
                        || $existing_var_atomic_type->as->hasScalar()
                        || $existing_var_atomic_type->as->hasNumeric()
                        || $existing_var_atomic_type->as->hasArrayKey()
                    ) {
                        if ($is_loose_equality) {
                            return $existing_var_type;
                        }

                        return new Type\Union([new Type\Atomic\TLiteralInt($value)]);
                    }

                    if ($existing_var_atomic_type->as->hasInt()) {
                        $has_int = true;
                    }
                }
            }

            if ($has_int) {
                $existing_int_types = $existing_var_type->getLiteralInts();

                if ($existing_int_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $atomic_type) {
                        if ($atomic_key !== $assertion
                            && !($atomic_type instanceof Type\Atomic\TPositiveInt && $value > 0)
                        ) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            $can_be_equal,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type = new Type\Union([new Type\Atomic\TLiteralInt($value)]);
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($is_loose_equality && $existing_var_type->hasFloat()) {
                // convert floats to ints
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if (substr($atomic_key, 0, 6) === 'float(') {
                            $atomic_key = 'int(' . substr($atomic_key, 6);
                        }
                        if ($atomic_key !== $assertion) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            $can_be_equal,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                }
            }
        } elseif ($scalar_type === 'string'
            || $scalar_type === 'class-string'
            || $scalar_type === 'interface-string'
            || $scalar_type === 'callable-string'
            || $scalar_type === 'trait-string'
        ) {
            if ($existing_var_type->hasMixed()
                || $existing_var_type->hasScalar()
                || $existing_var_type->hasArrayKey()
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                if ($scalar_type === 'class-string'
                    || $scalar_type === 'interface-string'
                    || $scalar_type === 'trait-string'
                ) {
                    return new Type\Union([new Type\Atomic\TLiteralClassString($value)]);
                }

                return new Type\Union([new Type\Atomic\TLiteralString($value)]);
            }

            $has_string = false;

            foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
                if ($existing_var_atomic_type instanceof TString) {
                    $has_string = true;
                } elseif ($existing_var_atomic_type instanceof TTemplateParam) {
                    if ($existing_var_atomic_type->as->hasMixed()
                        || $existing_var_atomic_type->as->hasString()
                        || $existing_var_atomic_type->as->hasScalar()
                        || $existing_var_atomic_type->as->hasArrayKey()
                    ) {
                        if ($is_loose_equality) {
                            return $existing_var_type;
                        }

                        $existing_var_atomic_type = clone $existing_var_atomic_type;

                        $existing_var_atomic_type->as = self::handleLiteralEquality(
                            $assertion,
                            $bracket_pos,
                            $is_loose_equality,
                            $existing_var_atomic_type->as,
                            $old_var_type_string,
                            $var_id,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );

                        return new Type\Union([$existing_var_atomic_type]);
                    }

                    if ($existing_var_atomic_type->as->hasString()) {
                        $has_string = true;
                    }
                }
            }

            if ($has_string) {
                $existing_string_types = $existing_var_type->getLiteralStrings();

                if ($existing_string_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if ($atomic_key !== $assertion) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            $can_be_equal,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    if ($scalar_type === 'class-string'
                        || $scalar_type === 'interface-string'
                        || $scalar_type === 'trait-string'
                    ) {
                        $existing_var_type = new Type\Union([new Type\Atomic\TLiteralClassString($value)]);
                    } else {
                        $existing_var_type = new Type\Union([new Type\Atomic\TLiteralString($value)]);
                    }
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        } elseif ($scalar_type === 'float') {
            $value = (float) $value;

            if ($existing_var_type->hasMixed() || $existing_var_type->hasScalar() || $existing_var_type->hasNumeric()) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                return new Type\Union([new Type\Atomic\TLiteralFloat($value)]);
            }

            if ($existing_var_type->hasFloat()) {
                $existing_float_types = $existing_var_type->getLiteralFloats();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if ($atomic_key !== $assertion) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            $can_be_equal,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                } else {
                    $existing_var_type = new Type\Union([new Type\Atomic\TLiteralFloat($value)]);
                }
            } elseif ($var_id && $code_location && !$is_loose_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($is_loose_equality && $existing_var_type->hasInt()) {
                // convert ints to floats
                $existing_float_types = $existing_var_type->getLiteralInts();

                if ($existing_float_types) {
                    $can_be_equal = false;
                    $did_remove_type = false;

                    foreach ($existing_var_atomic_types as $atomic_key => $_) {
                        if (substr($atomic_key, 0, 4) === 'int(') {
                            $atomic_key = 'float(' . substr($atomic_key, 4);
                        }
                        if ($atomic_key !== $assertion) {
                            $existing_var_type->removeType($atomic_key);
                            $did_remove_type = true;
                        } else {
                            $can_be_equal = true;
                        }
                    }

                    if ($var_id
                        && $code_location
                        && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
                    ) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            $can_be_equal,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                }
            }
        }

        return $existing_var_type;
    }
}
