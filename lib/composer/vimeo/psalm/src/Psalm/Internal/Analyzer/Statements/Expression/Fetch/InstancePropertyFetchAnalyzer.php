<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Config;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImpurePropertyFetch;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\PossiblyInvalidPropertyFetch;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\Issue\UndefinedMagicPropertyFetch;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\Issue\UninitializedProperty;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use function strtolower;
use function array_values;
use function in_array;
use function array_keys;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;

/**
 * @internal
 */
class InstancePropertyFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        bool $in_assignment = false
    ) : bool {
        $was_inside_use = $context->inside_use;
        $context->inside_use = true;

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        $context->inside_use = $was_inside_use;

        if ($stmt->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $stmt->name->name;
        } elseif (($stmt_name_type = $statements_analyzer->node_data->getType($stmt->name))
            && $stmt_name_type->isSingleStringLiteral()
        ) {
            $prop_name = $stmt_name_type->getSingleStringLiteral()->value;
        } else {
            $prop_name = null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $stmt_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id = ExpressionIdentifier::getArrayVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id && $context->hasVariable($var_id)) {
            self::handleScopedProperty(
                $context,
                $var_id,
                $statements_analyzer,
                $stmt,
                $codebase,
                $stmt_var_id,
                $in_assignment
            );

            return true;
        }

        if ($stmt_var_id && $context->hasVariable($stmt_var_id)) {
            $stmt_var_type = $context->vars_in_scope[$stmt_var_id];
        } else {
            $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if (!$stmt_var_type) {
            return true;
        }

        if ($stmt_var_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullPropertyFetch(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return true;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return true;
        }

        if ($stmt_var_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                $codebase->analyzer->addMixedMemberName(
                    '$' . $stmt->name->name,
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on mixed var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            $statements_analyzer->node_data->setType($stmt, Type::getMixed());

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $stmt_var_type->getId()
                );
            }
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getRootFilePath());
        }

        if ($stmt_var_type->isNullable() && !$stmt_var_type->ignore_nullable_issues) {
            if (!$context->inside_isset) {
                if (IssueBuffer::accepts(
                    new PossiblyNullPropertyFetch(
                        'Cannot get property on possibly null variable ' . $stmt_var_id . ' of type ' . $stmt_var_type,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getNull());
            }
        }

        if (!$prop_name) {
            if ($stmt_var_type->hasObjectType() && !$context->ignore_variable_property) {
                foreach ($stmt_var_type->getAtomicTypes() as $type) {
                    if ($type instanceof Type\Atomic\TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($type->value) . '::$',
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }
                }
            }

            return true;
        }

        $invalid_fetch_types = [];
        $has_valid_fetch_type = false;

        foreach ($stmt_var_type->getAtomicTypes() as $lhs_type_part) {
            AtomicPropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $in_assignment,
                $var_id,
                $stmt_var_id,
                $stmt_var_type,
                $lhs_type_part,
                $prop_name,
                $has_valid_fetch_type,
                $invalid_fetch_types
            );
        }

        $stmt_type = $statements_analyzer->node_data->getType($stmt);

        if ($stmt_var_type->isNullable() && !$context->inside_isset && $stmt_type) {
            $stmt_type->addType(new TNull);

            if ($stmt_var_type->ignore_nullable_issues) {
                $stmt_type->ignore_nullable_issues = true;
            }
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId()
            );
        }

        if ($invalid_fetch_types) {
            $lhs_type_part = $invalid_fetch_types[0];

            if ($has_valid_fetch_type) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidPropertyFetch(
                        'Cannot fetch property on possible non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $statements_analyzer->node_data->getType($stmt) ?: Type::getMixed();
        }

        return true;
    }

    private static function handleScopedProperty(
        Context $context,
        string $var_id,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        \Psalm\Codebase $codebase,
        ?string $stmt_var_id,
        bool $in_assignment
    ): void {
        $stmt_type = $context->vars_in_scope[$var_id];

        // we don't need to check anything
        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId()
            );
        }

        if ($stmt_var_id === '$this'
            && !$stmt_type->initialized
            && $context->collect_initializations
            && ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $stmt_var_type->hasObjectType()
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            $source = $statements_analyzer->getSource();

            $property_id = null;

            foreach ($stmt_var_type->getAtomicTypes() as $lhs_type_part) {
                if ($lhs_type_part instanceof TNamedObject) {
                    if (!$codebase->classExists($lhs_type_part->value)) {
                        continue;
                    }

                    $property_id = $lhs_type_part->value . '::$' . $stmt->name->name;
                }
            }

            if ($property_id
                && $source instanceof FunctionLikeAnalyzer
                && $source->getMethodName() === '__construct'
                && !$context->inside_unset
            ) {
                if ($context->inside_isset
                    || ($context->inside_assignment
                        && isset($context->vars_in_scope[$var_id])
                        && $context->vars_in_scope[$var_id]->isNullable()
                    )
                ) {
                    $stmt_type->initialized = true;
                } else {
                    if (IssueBuffer::accepts(
                        new UninitializedProperty(
                            'Cannot use uninitialized property ' . $var_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $stmt_type->addType(new Type\Atomic\TNull);
                }
            }
        }

        if (($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $stmt_var_type->hasObjectType()
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            // log the appearance
            foreach ($stmt_var_type->getAtomicTypes() as $lhs_type_part) {
                if ($lhs_type_part instanceof TNamedObject) {
                    if (!$codebase->classExists($lhs_type_part->value)) {
                        continue;
                    }

                    $property_id = $lhs_type_part->value . '::$' . $stmt->name->name;

                    $class_storage = $codebase->classlike_storage_provider->get($lhs_type_part->value);

                    AtomicPropertyFetchAnalyzer::processTaints(
                        $statements_analyzer,
                        $stmt,
                        $stmt_type,
                        $property_id,
                        $class_storage,
                        $in_assignment
                    );

                    $codebase->properties->propertyExists(
                        $property_id,
                        true,
                        $statements_analyzer,
                        $context,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                            : null
                    );

                    if ($codebase->store_node_types
                        && !$context->collect_initializations
                        && !$context->collect_mutations
                    ) {
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $stmt->name,
                            $property_id
                        );
                    }

                    if (!$context->collect_mutations
                        && !$context->collect_initializations
                        && !($class_storage->external_mutation_free
                            && $stmt_type->allow_mutations)
                    ) {
                        if ($context->pure) {
                            if (IssueBuffer::accepts(
                                new ImpurePropertyFetch(
                                    'Cannot access a property on a mutable object from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($statements_analyzer->getSource()
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }
                }
            }
        }
    }
}
