<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function strtolower;
use function in_array;
use function count;
use function explode;

/**
 * @internal
 */
class StaticPropertyFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        Context $context
    ) : bool {
        if (!$stmt->class instanceof PhpParser\Node\Name) {
            self::analyzeVariableStaticPropertyFetch($statements_analyzer, $stmt->class, $stmt, $context);
            return true;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (count($stmt->class->parts) === 1
            && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
        ) {
            if ($stmt->class->parts[0] === 'parent') {
                $fq_class_name = $statements_analyzer->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return true;
                }
            } else {
                $fq_class_name = (string)$context->self;
            }

            if ($context->isPhantomClass($fq_class_name)) {
                return true;
            }
        } else {
            $aliases = $statements_analyzer->getAliases();

            if ($context->calling_method_id
                && !$stmt->class instanceof PhpParser\Node\Name\FullyQualified
            ) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    'use:' . $stmt->class->parts[0] . ':' . \md5($statements_analyzer->getFilePath())
                );
            }

            $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $stmt->class,
                $aliases
            );

            if ($context->isPhantomClass($fq_class_name)) {
                return true;
            }

            if ($context->check_classes) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    false
                ) !== true) {
                    return false;
                }
            }
        }

        if ($fq_class_name
            && $codebase->methods_to_move
            && $context->calling_method_id
            && isset($codebase->methods_to_move[$context->calling_method_id])
        ) {
            $destination_method_id = $codebase->methods_to_move[$context->calling_method_id];

            $codebase->classlikes->airliftClassLikeReference(
                $fq_class_name,
                explode('::', $destination_method_id)[0],
                $statements_analyzer->getFilePath(),
                (int) $stmt->class->getAttribute('startFilePos'),
                (int) $stmt->class->getAttribute('endFilePos') + 1
            );
        }

        if ($fq_class_name) {
            $statements_analyzer->node_data->setType(
                $stmt->class,
                new Type\Union([new TNamedObject($fq_class_name)])
            );
        }

        if ($stmt->name instanceof PhpParser\Node\VarLikeIdentifier) {
            $prop_name = $stmt->name->name;
        } elseif (($stmt_name_type = $statements_analyzer->node_data->getType($stmt->name))
            && $stmt_name_type->isSingleStringLiteral()
        ) {
            $prop_name = $stmt_name_type->getSingleStringLiteral()->value;
        } else {
            $prop_name = null;
        }

        if (!$prop_name) {
            if ($fq_class_name) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::$',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            return true;
        }

        if (!$fq_class_name
            || !$context->check_classes
            || !$context->check_variables
            || ExpressionAnalyzer::isMock($fq_class_name)
        ) {
            return true;
        }

        $var_id = ExpressionIdentifier::getVarId(
            $stmt,
            $context->self ?: $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $property_id = $fq_class_name . '::$' . $prop_name;

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

        if ($context->mutation_free) {
            if (IssueBuffer::accepts(
                new \Psalm\Issue\ImpureStaticProperty(
                    'Cannot use a static property in a mutation-free context',
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
            $statements_analyzer->getSource()->inferred_has_mutation = true;
            $statements_analyzer->getSource()->inferred_impure = true;
        }

        if ($var_id && $context->hasVariable($var_id)) {
            $stmt_type = $context->vars_in_scope[$var_id];

            // we don't need to check anything
            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if ($codebase->collect_references) {
                // log the appearance
                $codebase->properties->propertyExists(
                    $property_id,
                    true,
                    $statements_analyzer,
                    $context,
                    $codebase->collect_locations
                        ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                        : null
                );
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

            return true;
        }

        if (!$codebase->properties->propertyExists(
            $property_id,
            true,
            $statements_analyzer,
            $context,
            $codebase->collect_locations
                ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                : null
        )
        ) {
            if ($context->inside_isset) {
                return true;
            }

            if (IssueBuffer::accepts(
                new UndefinedPropertyFetch(
                    'Static property ' . $property_id . ' is not defined',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $property_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return true;
        }

        if (ClassLikeAnalyzer::checkPropertyVisibility(
            $property_id,
            $context,
            $statements_analyzer,
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $statements_analyzer->getSuppressedIssues()
        ) === false) {
            return false;
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $fq_class_name . '::$' . $prop_name,
            true,
            $statements_analyzer
        );

        if ($declaring_property_class === null) {
            return false;
        }

        $declaring_property_id = strtolower($declaring_property_class) . '::$' . $prop_name;

        if ($codebase->alter_code) {
            $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $statements_analyzer,
                $stmt->class,
                $fq_class_name,
                $context->calling_method_id
            );

            if (!$moved_class) {
                foreach ($codebase->property_transforms as $original_pattern => $transformation) {
                    if ($declaring_property_id === $original_pattern) {
                        [$old_declaring_fq_class_name] = explode('::$', $declaring_property_id);
                        [$new_fq_class_name, $new_property_name] = explode('::$', $transformation);

                        $file_manipulations = [];

                        if (strtolower($new_fq_class_name) !== strtolower($old_declaring_fq_class_name)) {
                            $file_manipulations[] = new \Psalm\FileManipulation(
                                (int) $stmt->class->getAttribute('startFilePos'),
                                (int) $stmt->class->getAttribute('endFilePos') + 1,
                                Type::getStringFromFQCLN(
                                    $new_fq_class_name,
                                    $statements_analyzer->getNamespace(),
                                    $statements_analyzer->getAliasedClassesFlipped(),
                                    null
                                )
                            );
                        }

                        $file_manipulations[] = new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            '$' . $new_property_name
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }
        }

        $class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);
        $property = $class_storage->properties[$prop_name];

        if ($var_id) {
            if ($property->type) {
                $context->vars_in_scope[$var_id] = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    clone $property->type,
                    $class_storage->name,
                    $class_storage->name,
                    $class_storage->parent_class
                );
            } else {
                $context->vars_in_scope[$var_id] = Type::getMixed();
            }

            $stmt_type = clone $context->vars_in_scope[$var_id];

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

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
        } else {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }

    private static function analyzeVariableStaticPropertyFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt_class,
        PhpParser\Node\Expr\StaticPropertyFetch $stmt,
        Context $context
    ) : void {
        $was_inside_use = $context->inside_use;

        $context->inside_use = true;

        ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $stmt_class,
            $context
        );

        $context->inside_use = $was_inside_use;

        $stmt_class_type = $statements_analyzer->node_data->getType($stmt_class) ?: Type::getMixed();

        $old_data_provider = $statements_analyzer->node_data;

        $stmt_type = null;

        $codebase = $statements_analyzer->getCodebase();

        foreach ($stmt_class_type->getAtomicTypes() as $class_atomic_type) {
            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $string_type = ($class_atomic_type instanceof Type\Atomic\TClassString
                    && $class_atomic_type->as_type !== null)
                ? $class_atomic_type->as_type->value
                : ($class_atomic_type instanceof Type\Atomic\TLiteralString
                    ? $class_atomic_type->value
                    : null);

            if ($string_type) {
                $new_stmt_name = new PhpParser\Node\Name\FullyQualified(
                    $string_type,
                    $stmt_class->getAttributes()
                );

                $fake_static_property = new PhpParser\Node\Expr\StaticPropertyFetch(
                    $new_stmt_name,
                    $stmt->name,
                    $stmt->getAttributes()
                );

                self::analyze($statements_analyzer, $fake_static_property, $context);

                $fake_stmt_type = $statements_analyzer->node_data->getType($fake_static_property)
                    ?: Type::getMixed();
            } else {
                $fake_var_name = '__fake_var_' . (string) $stmt->getAttribute('startFilePos');

                $fake_var = new PhpParser\Node\Expr\Variable(
                    $fake_var_name,
                    $stmt_class->getAttributes()
                );

                $context->vars_in_scope['$' . $fake_var_name] = new Type\Union([$class_atomic_type]);

                $fake_instance_property = new PhpParser\Node\Expr\PropertyFetch(
                    $fake_var,
                    $stmt->name,
                    $stmt->getAttributes()
                );

                InstancePropertyFetchAnalyzer::analyze(
                    $statements_analyzer,
                    $fake_instance_property,
                    $context
                );

                $fake_stmt_type = $statements_analyzer->node_data->getType($fake_instance_property)
                    ?: Type::getMixed();
            }

            $stmt_type = $stmt_type
                ? Type::combineUnionTypes($stmt_type, $fake_stmt_type, $codebase)
                : $fake_stmt_type;

            $statements_analyzer->node_data = $old_data_provider;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);
    }
}
