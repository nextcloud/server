<?php

namespace Psalm\Internal\Type;

use function get_class;
use Psalm\CodeLocation;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use function substr;

class SimpleNegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     * @param  string[]   $suppressed_issues
     * @param  0|1|2      $failed_reconciliation
     *
     * @return Type\Union
     */
    public static function reconcile(
        string $assertion,
        Type\Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = 0,
        bool $is_equality = false,
        bool $is_strict_equality = false
    ) : ?Type\Union {
        if ($assertion === 'object' && !$existing_var_type->hasMixed()) {
            return self::reconcileObject(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'scalar' && !$existing_var_type->hasMixed()) {
            return self::reconcileScalar(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'resource' && !$existing_var_type->hasMixed()) {
            return self::reconcileResource(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'bool' && !$existing_var_type->hasMixed()) {
            return self::reconcileBool(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'numeric' && !$existing_var_type->hasMixed()) {
            return self::reconcileNumeric(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'float' && !$existing_var_type->hasMixed()) {
            return self::reconcileFloat(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'int' && !$existing_var_type->hasMixed()) {
            return self::reconcileInt(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string' && !$existing_var_type->hasMixed()) {
            return self::reconcileString(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'array' && !$existing_var_type->hasMixed()) {
            return self::reconcileArray(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'falsy' || $assertion === 'empty') {
            return self::reconcileFalsyOrEmpty(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'null' && !$existing_var_type->hasMixed()) {
            return self::reconcileNull(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'false' && !$existing_var_type->hasMixed()) {
            return self::reconcileFalse(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'non-empty-countable') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                null
            );
        }

        if ($assertion === 'callable') {
            return self::reconcileCallable(
                $existing_var_type
            );
        }

        if (substr($assertion, 0, 13) === 'has-at-least-') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                (int) substr($assertion, 13)
            );
        }

        if (substr($assertion, 0, 12) === 'has-exactly-') {
            return $existing_var_type;
        }

        return null;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileCallable(
        Type\Union $existing_var_type
    ) : Type\Union {
        foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $type) {
            if ($type instanceof Type\Atomic\TLiteralString
                && \Psalm\Internal\Codebase\InternalCallMapHandler::inCallMap($type->value)
            ) {
                $existing_var_type->removeType($atomic_key);
            }

            if ($type->isCallableType()) {
                $existing_var_type->removeType($atomic_key);
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileBool(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_bool_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasBool()) {
                    $non_bool_types[] = $type;
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TBool
                || ($is_equality && get_class($type) === TBool::class)
            ) {
                if ($type instanceof TScalar) {
                    $did_remove_type = true;
                    $non_bool_types[] = new TString();
                    $non_bool_types[] = new TInt();
                    $non_bool_types[] = new TFloat();
                } else {
                    $non_bool_types[] = $type;
                }
            } else {
                $did_remove_type = true;
            }
        }

        if (!$did_remove_type || !$non_bool_types) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!bool',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_bool_types) {
            return new Type\Union($non_bool_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNonEmptyCountable(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        ?int $min_count
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if (isset($existing_var_atomic_types['array'])) {
            $array_atomic_type = $existing_var_atomic_types['array'];
            $did_remove_type = false;

            if (($array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                    || $array_atomic_type instanceof Type\Atomic\TNonEmptyList)
                && ($min_count === null
                    || $array_atomic_type->count >= $min_count)
            ) {
                $did_remove_type = true;

                $existing_var_type->removeType('array');
            } elseif ($array_atomic_type->getId() !== 'array<empty, empty>') {
                $did_remove_type = true;

                if (!$min_count) {
                    $existing_var_type->addType(new TArray(
                        [
                            new Type\Union([new TEmpty]),
                            new Type\Union([new TEmpty]),
                        ]
                    ));
                }
            } elseif ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                $did_remove_type = true;

                foreach ($array_atomic_type->properties as $property_type) {
                    if (!$property_type->possibly_undefined) {
                        $did_remove_type = false;
                        break;
                    }
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && (!$did_remove_type || empty($existing_var_type->getAtomicTypes()))
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!non-empty-countable',
                        !$did_remove_type,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNull(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = false;

        if ($existing_var_type->hasType('null')) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileNull(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || empty($existing_var_type->getAtomicTypes())) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!null',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileFalse(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = $existing_var_type->hasScalar();

        if ($existing_var_type->hasType('false')) {
            $did_remove_type = true;
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileFalse(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || empty($existing_var_type->getAtomicTypes())) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!false',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileFalsyOrEmpty(
        string $assertion,
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_strict_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = $existing_var_type->hasDefinitelyNumericType(false)
            || $existing_var_type->isEmpty()
            || $existing_var_type->hasType('bool')
            || $existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try
            || $existing_var_type->hasType('iterable');

        if ($is_strict_equality && $assertion === 'empty') {
            $existing_var_type->removeType('null');
            $existing_var_type->removeType('false');

            if ($existing_var_type->hasType('array')
                && $existing_var_type->getAtomicTypes()['array']->getId() === 'array<empty, empty>'
            ) {
                $existing_var_type->removeType('array');
            }

            if ($existing_var_type->hasMixed()) {
                $existing_var_type->removeType('mixed');

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyMixed);
                }
            }

            if ($existing_var_type->hasScalar()) {
                $existing_var_type->removeType('scalar');

                if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TEmptyScalar) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyScalar);
                }
            }

            if (isset($existing_var_atomic_types['string'])) {
                $existing_var_type->removeType('string');

                if ($existing_var_atomic_types['string'] instanceof Type\Atomic\TLowercaseString) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyLowercaseString);
                } else {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyString);
                }
            }

            self::removeFalsyNegatedLiteralTypes(
                $existing_var_type,
                $did_remove_type
            );

            $existing_var_type->possibly_undefined = false;
            $existing_var_type->possibly_undefined_from_try = false;

            if ($existing_var_type->getAtomicTypes()) {
                return $existing_var_type;
            }

            $failed_reconciliation = 2;

            return Type::getMixed();
        }

        if ($existing_var_type->hasMixed()) {
            if ($existing_var_type->isMixed()
                && $existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed
            ) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a paradox when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }

                return Type::getMixed();
            }

            if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed) {
                $did_remove_type = true;
                $existing_var_type->removeType('mixed');

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyMixed);
                }
            } elseif ($existing_var_type->isMixed() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location,
                            $existing_var_type->getId() . ' ' . $assertion
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isMixed()) {
                return $existing_var_type;
            }
        }

        if ($existing_var_type->hasScalar()) {
            if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TNonEmptyScalar) {
                $did_remove_type = true;
                $existing_var_type->removeType('scalar');

                if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TEmptyScalar) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyScalar);
                }
            } elseif ($existing_var_type->isSingle() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location,
                            $existing_var_type->getId() . ' ' . $assertion
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
        }

        if (isset($existing_var_atomic_types['string'])) {
            if (!$existing_var_atomic_types['string'] instanceof Type\Atomic\TNonEmptyString
                && !$existing_var_atomic_types['string'] instanceof Type\Atomic\TClassString
                && !$existing_var_atomic_types['string'] instanceof Type\Atomic\TDependentGetClass
            ) {
                $did_remove_type = true;

                $existing_var_type->removeType('string');

                if ($existing_var_atomic_types['string'] instanceof Type\Atomic\TLowercaseString) {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyLowercaseString);
                } else {
                    $existing_var_type->addType(new Type\Atomic\TNonEmptyString);
                }
            } elseif ($existing_var_type->isSingle() && !$is_equality) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a non-' . $assertion . ' assertion',
                            $code_location,
                            $existing_var_type->getId() . ' ' . $assertion
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
        }

        if ($existing_var_type->hasType('null')) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        if ($existing_var_type->hasType('false')) {
            $did_remove_type = true;
            $existing_var_type->removeType('false');
        }

        if ($existing_var_type->hasType('bool')) {
            $did_remove_type = true;
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue);
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof Type\Atomic\TTemplateParam) {
                if (!$is_equality && !$existing_var_atomic_type->as->isMixed()) {
                    $template_did_fail = 0;

                    $existing_var_atomic_type = clone $existing_var_atomic_type;

                    $existing_var_atomic_type->as = self::reconcileFalsyOrEmpty(
                        $assertion,
                        $existing_var_atomic_type->as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality,
                        $is_strict_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $existing_var_type->addType($existing_var_atomic_type);
                    }
                }
            }
        }

        self::removeFalsyNegatedLiteralTypes(
            $existing_var_type,
            $did_remove_type
        );

        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;

        if ((!$did_remove_type || empty($existing_var_type->getAtomicTypes())) && !$existing_var_type->hasTemplate()) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!' . $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileScalar(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_scalar_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileScalar(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_scalar_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_scalar_types[] = $type;
                }
            } elseif (!($type instanceof Scalar)) {
                $non_scalar_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_scalar_types[] = $type;
                }
            }
        }

        if (!$did_remove_type || !$non_scalar_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!scalar',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_scalar_types) {
            $type = new Type\Union($non_scalar_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileObject(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileObject(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_object_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_object_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_object_types[] = new Atomic\TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_object_types[] = new Atomic\TCallableString();
                $did_remove_type = true;
            } elseif (!$type->isObjectType()) {
                $non_object_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_object_types[] = $type;
                }
            }
        }

        if (!$non_object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!object',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_object_types) {
            $type = new Type\Union($non_object_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNumeric(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_numeric_types = [];
        $did_remove_type = $existing_var_type->hasString()
            || $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileNumeric(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_numeric_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_numeric_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $non_numeric_types[] = new TString();
            } elseif (!$type->isNumericType()) {
                $non_numeric_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_numeric_types[] = $type;
                }
            }
        }

        if (!$non_numeric_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_numeric_types) {
            $type = new Type\Union($non_numeric_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileInt(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_int_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileInt(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_int_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_int_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $non_int_types[] = new TString();
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_int_types[] = new TString();
                $non_int_types[] = new TFloat();
                $non_int_types[] = new TBool();
            } elseif ($type instanceof TInt) {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_int_types[] = $type;
                } elseif ($existing_var_type->from_calculation) {
                    $non_int_types[] = new TFloat();
                }
            } else {
                $non_int_types[] = $type;
            }
        }

        if (!$non_int_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!int',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_int_types) {
            $type = new Type\Union($non_int_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileFloat(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_float_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileFloat(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_float_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_float_types[] = $type;
                }
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_float_types[] = new TString();
                $non_float_types[] = new TInt();
                $non_float_types[] = new TBool();
            } elseif ($type instanceof TFloat) {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_float_types[] = $type;
                }
            } else {
                $non_float_types[] = $type;
            }
        }

        if (!$non_float_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!float',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_float_types) {
            $type = new Type\Union($non_float_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileString(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_string_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileString(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_string_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_string_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $non_string_types[] = new TInt();
                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $non_string_types[] = new Atomic\TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_string_types[] = new Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $non_string_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_string_types[] = new TFloat();
                $non_string_types[] = new TInt();
                $non_string_types[] = new TBool();
            } elseif (!$type instanceof TString) {
                $non_string_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_string_types[] = $type;
                }
            }
        }

        if (!$non_string_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!string',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_string_types) {
            $type = new Type\Union($non_string_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileArray(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_array_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileArray(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_array_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_array_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_array_types[] = new Atomic\TCallableString();
                $non_array_types[] = new Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof Atomic\TIterable) {
                if (!$type->type_params[0]->isMixed() || !$type->type_params[1]->isMixed()) {
                    $non_array_types[] = new Atomic\TGenericObject('Traversable', $type->type_params);
                } else {
                    $non_array_types[] = new TNamedObject('Traversable');
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TArray
                && !$type instanceof TKeyedArray
                && !$type instanceof Atomic\TList
            ) {
                $non_array_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_array_types[] = $type;
                }
            }
        }

        if ((!$non_array_types || !$did_remove_type)) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($non_array_types) {
            $type = new Type\Union($non_array_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileResource(
        Type\Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Type\Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = false;

        if ($existing_var_type->hasType('resource')) {
            $did_remove_type = true;
            $existing_var_type->removeType('resource');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileResource(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || empty($existing_var_type->getAtomicTypes())) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!resource',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = 1;
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    private static function removeFalsyNegatedLiteralTypes(
        Type\Union $existing_var_type,
        bool &$did_remove_type
    ): void {
        if ($existing_var_type->hasString()) {
            $existing_string_types = $existing_var_type->getLiteralStrings();

            if ($existing_string_types) {
                foreach ($existing_string_types as $string_key => $literal_type) {
                    if (!$literal_type->value) {
                        $existing_var_type->removeType($string_key);
                        $did_remove_type = true;
                    }
                }
            } else {
                $did_remove_type = true;
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_int_types = $existing_var_type->getLiteralInts();

            if ($existing_int_types) {
                foreach ($existing_int_types as $int_key => $literal_type) {
                    if (!$literal_type->value) {
                        $existing_var_type->removeType($int_key);
                        $did_remove_type = true;
                    }
                }
            } else {
                $did_remove_type = true;
            }
        }

        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof Type\Atomic\TArray
                && !$array_atomic_type instanceof Type\Atomic\TNonEmptyArray
            ) {
                $did_remove_type = true;

                if ($array_atomic_type->getId() === 'array<empty, empty>') {
                    $existing_var_type->removeType('array');
                } else {
                    $existing_var_type->addType(
                        new Type\Atomic\TNonEmptyArray(
                            $array_atomic_type->type_params
                        )
                    );
                }
            } elseif ($array_atomic_type instanceof Type\Atomic\TList
                && !$array_atomic_type instanceof Type\Atomic\TNonEmptyList
            ) {
                $did_remove_type = true;

                $existing_var_type->addType(
                    new Type\Atomic\TNonEmptyList(
                        $array_atomic_type->type_param
                    )
                );
            } elseif ($array_atomic_type instanceof Type\Atomic\TKeyedArray
                && !$array_atomic_type->sealed
            ) {
                $did_remove_type = true;
            }
        }
    }
}
