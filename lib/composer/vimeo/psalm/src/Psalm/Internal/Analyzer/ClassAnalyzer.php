<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\FileManipulation\PropertyDocblockManipulator;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\DeprecatedTrait;
use Psalm\Issue\ExtensionRequirementViolation;
use Psalm\Issue\ImplementationRequirementViolation;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidExtendClass;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MissingConstructor;
use Psalm\Issue\MissingImmutableAnnotation;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\MutableDependency;
use Psalm\Issue\OverriddenPropertyAccess;
use Psalm\Issue\PropertyNotSetInConstructor;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedInterface;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedAbstractMethod;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use function preg_replace;
use function preg_match;
use function explode;
use function array_pop;
use function strtolower;
use function implode;
use function substr;
use function array_map;
use function str_replace;
use function count;
use function array_search;
use function array_keys;
use function array_merge;
use function array_filter;
use function in_array;

/**
 * @internal
 */
class ClassAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @var array<string, Type\Union>
     */
    public $inferred_property_types = [];

    public function __construct(PhpParser\Node\Stmt\Class_ $class, SourceAnalyzer $source, ?string $fq_class_name)
    {
        if (!$fq_class_name) {
            $fq_class_name = self::getAnonymousClassName($class, $source->getFilePath());
        }

        parent::__construct($class, $source, $fq_class_name);

        if (!$this->class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \InvalidArgumentException('Bad');
        }

        if ($this->class->extends) {
            $this->parent_fq_class_name = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->source->getAliases()
            );
        }
    }

    public static function getAnonymousClassName(PhpParser\Node\Stmt\Class_ $class, string $file_path): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '_', $file_path)
            . '_' . $class->getLine() . '_' . (int)$class->getAttribute('startFilePos');
    }

    /**
     * @return null|false
     */
    public function analyze(
        ?Context $class_context = null,
        ?Context $global_context = null
    ): ?bool {
        $class = $this->class;

        if (!$class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \LogicException('Something went badly wrong');
        }

        $fq_class_name = $class_context && $class_context->self ? $class_context->self : $this->fq_class_name;

        $storage = $this->storage;

        if ($storage->has_visitor_issues) {
            return null;
        }

        if ($class->name
            && (preg_match(
                '/(^|\\\)(int|float|bool|string|void|null|false|true|object|mixed)$/i',
                $fq_class_name
            ) || strtolower($fq_class_name) === 'resource')
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (IssueBuffer::accepts(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    new CodeLocation(
                        $this,
                        $class->name,
                        null,
                        true
                    ),
                    $class_name
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $this->getCodebase();

        if ($codebase->alter_code && $class->name && $codebase->classes_to_move) {
            if (isset($codebase->classes_to_move[strtolower($this->fq_class_name)])) {
                $destination_class = $codebase->classes_to_move[strtolower($this->fq_class_name)];

                $source_class_parts = explode('\\', $this->fq_class_name);
                $destination_class_parts = explode('\\', $destination_class);

                array_pop($source_class_parts);
                array_pop($destination_class_parts);

                $source_ns = implode('\\', $source_class_parts);
                $destination_ns = implode('\\', $destination_class_parts);

                if (strtolower($source_ns) !== strtolower($destination_ns)) {
                    if ($storage->namespace_name_location) {
                        $bounds = $storage->namespace_name_location->getSelectionBounds();

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $bounds[0],
                                $bounds[1],
                                $destination_ns
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    } elseif (!$source_ns) {
                        $first_statement_pos = $this->getFileAnalyzer()->getFirstStatementOffset();

                        if ($first_statement_pos === -1) {
                            $first_statement_pos = (int) $class->getAttribute('startFilePos');
                        }

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $first_statement_pos,
                                $first_statement_pos,
                                'namespace ' . $destination_ns . ';' . "\n\n",
                                true
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    }
                }
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $class->name,
                $this->fq_class_name,
                null
            );
        }

        foreach ($storage->docblock_issues as $docblock_issue) {
            IssueBuffer::add($docblock_issue);
        }

        $classlike_storage_provider = $codebase->classlike_storage_provider;

        $parent_fq_class_name = $this->parent_fq_class_name;

        if ($class->extends) {
            if (!$parent_fq_class_name) {
                throw new \UnexpectedValueException('Parent class should be filled in for ' . $fq_class_name);
            }

            $parent_reference_location = new CodeLocation($this, $class->extends);

            if (self::checkFullyQualifiedClassLikeName(
                $this->getSource(),
                $parent_fq_class_name,
                $parent_reference_location,
                null,
                null,
                $storage->suppressed_issues + $this->getSuppressedIssues(),
                false
            ) === false) {
                return false;
            }

            if ($codebase->alter_code && $codebase->classes_to_move) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $this,
                    $class->extends,
                    $parent_fq_class_name,
                    null
                );
            }

            try {
                $parent_class_storage = $classlike_storage_provider->get($parent_fq_class_name);

                $code_location = new CodeLocation(
                    $this,
                    $class->extends,
                    $class_context ? $class_context->include_location : null,
                    true
                );

                if ($parent_class_storage->is_trait || $parent_class_storage->is_interface) {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            $parent_fq_class_name . ' is not a class',
                            $code_location,
                            $parent_fq_class_name . ' as class'
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent_class_storage->final) {
                    if (IssueBuffer::accepts(
                        new InvalidExtendClass(
                            'Class ' . $fq_class_name  . ' may not inherit from final class ' . $parent_fq_class_name,
                            $code_location,
                            $fq_class_name
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent_class_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $parent_fq_class_name . ' is marked deprecated',
                            $code_location,
                            $parent_fq_class_name
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (! NamespaceAnalyzer::isWithin($fq_class_name, $parent_class_storage->internal)) {
                    if (IssueBuffer::accepts(
                        new InternalClass(
                            $parent_fq_class_name . ' is internal to ' . $parent_class_storage->internal
                                . ' but called from ' . $fq_class_name,
                            $code_location,
                            $parent_fq_class_name
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent_class_storage->external_mutation_free
                    && !$storage->external_mutation_free
                ) {
                    if (IssueBuffer::accepts(
                        new MissingImmutableAnnotation(
                            $parent_fq_class_name . ' is marked immutable, but '
                                . $fq_class_name . ' is not marked immutable',
                            $code_location
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($storage->mutation_free
                    && !$parent_class_storage->mutation_free
                ) {
                    if (IssueBuffer::accepts(
                        new MutableDependency(
                            $fq_class_name . ' is marked immutable but ' . $parent_fq_class_name . ' is not',
                            $code_location
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($codebase->store_node_types) {
                    $codebase->analyzer->addNodeReference(
                        $this->getFilePath(),
                        $class->extends,
                        $codebase->classlikes->classExists($parent_fq_class_name)
                            ? $parent_fq_class_name
                            : '*' . implode('\\', $class->extends->parts)
                    );
                }

                $code_location = new CodeLocation(
                    $this,
                    $class->name ?: $class,
                    $class_context ? $class_context->include_location : null,
                    true
                );

                if ($storage->template_type_extends_count !== null) {
                    $this->checkTemplateParams(
                        $codebase,
                        $storage,
                        $parent_class_storage,
                        $code_location,
                        $storage->template_type_extends_count
                    );
                }
            } catch (\InvalidArgumentException $e) {
                // do nothing
            }
        }

        foreach ($class->implements as $interface_name) {
            $fq_interface_name = self::getFQCLNFromNameObject(
                $interface_name,
                $this->source->getAliases()
            );

            $codebase->analyzer->addNodeReference(
                $this->getFilePath(),
                $interface_name,
                $codebase->classlikes->interfaceExists($fq_interface_name)
                    ? $fq_interface_name
                    : '*' . implode('\\', $interface_name->parts)
            );

            $interface_location = new CodeLocation($this, $interface_name);

            if (self::checkFullyQualifiedClassLikeName(
                $this,
                $fq_interface_name,
                $interface_location,
                null,
                null,
                $this->getSuppressedIssues(),
                false
            ) === false) {
                continue;
            }

            if ($codebase->store_node_types && $fq_class_name) {
                $bounds = $interface_location->getSelectionBounds();

                $codebase->analyzer->addOffsetReference(
                    $this->getFilePath(),
                    $bounds[0],
                    $bounds[1],
                    $fq_interface_name
                );
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $interface_name,
                $fq_interface_name,
                null
            );

            $fq_interface_name_lc = strtolower($fq_interface_name);

            try {
                $interface_storage = $classlike_storage_provider->get($fq_interface_name_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $code_location = new CodeLocation(
                $this,
                $interface_name,
                $class_context ? $class_context->include_location : null,
                true
            );

            if (!$interface_storage->is_interface) {
                if (IssueBuffer::accepts(
                    new UndefinedInterface(
                        $fq_interface_name . ' is not an interface',
                        $code_location,
                        $fq_interface_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if (isset($storage->template_type_implements_count[$fq_interface_name_lc])) {
                $expected_param_count = $storage->template_type_implements_count[$fq_interface_name_lc];

                $this->checkTemplateParams(
                    $codebase,
                    $storage,
                    $interface_storage,
                    $code_location,
                    $expected_param_count
                );
            }
        }

        if ($storage->template_types) {
            foreach ($storage->template_types as $param_name => $_) {
                $fq_classlike_name = Type::getFQCLNFromString(
                    $param_name,
                    $this->getAliases()
                );

                if ($codebase->classOrInterfaceExists($fq_classlike_name)) {
                    if (IssueBuffer::accepts(
                        new ReservedWord(
                            'Cannot use ' . $param_name . ' as template name since the class already exists',
                            new CodeLocation($this, $this->class),
                            'resource'
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if (($storage->templatedMixins || $storage->namedMixins)
            && $storage->mixin_declaring_fqcln === $storage->name) {
            /** @var non-empty-array<int, Type\Atomic\TTemplateParam|Type\Atomic\TNamedObject> $mixins */
            $mixins = array_merge($storage->templatedMixins, $storage->namedMixins);
            $union = new Type\Union($mixins);
            $union->check(
                $this,
                new CodeLocation(
                    $this,
                    $class->name ?: $class,
                    null,
                    true
                ),
                $this->getSuppressedIssues()
            );
        }

        if ($storage->template_type_extends) {
            foreach ($storage->template_type_extends as $type_map) {
                foreach ($type_map as $atomic_type) {
                    $atomic_type->check(
                        $this,
                        new CodeLocation(
                            $this,
                            $class->name ?: $class,
                            null,
                            true
                        ),
                        $this->getSuppressedIssues()
                    );
                }
            }
        }

        if ($storage->invalid_dependencies) {
            return null;
        }

        $class_interfaces = $storage->class_implements;

        foreach ($class_interfaces as $interface_name) {
            try {
                $interface_storage = $classlike_storage_provider->get($interface_name);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $code_location = new CodeLocation(
                $this,
                $class->name ? $class->name : $class,
                $class_context ? $class_context->include_location : null,
                true
            );

            if ($interface_storage->deprecated) {
                if (IssueBuffer::accepts(
                    new DeprecatedInterface(
                        $interface_name . ' is marked deprecated',
                        $code_location,
                        $interface_name
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($interface_storage->external_mutation_free
                && !$storage->external_mutation_free
            ) {
                if (IssueBuffer::accepts(
                    new MissingImmutableAnnotation(
                        $interface_name . ' is marked immutable, but '
                            . $fq_class_name . ' is not marked immutable',
                        $code_location
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            foreach ($interface_storage->methods as $interface_method_name_lc => $interface_method_storage) {
                if ($interface_method_storage->visibility === self::VISIBILITY_PUBLIC) {
                    $implementer_declaring_method_id = $codebase->methods->getDeclaringMethodId(
                        new \Psalm\Internal\MethodIdentifier(
                            $this->fq_class_name,
                            $interface_method_name_lc
                        )
                    );

                    $implementer_method_storage = null;
                    $implementer_classlike_storage = null;

                    if ($implementer_declaring_method_id) {
                        $implementer_fq_class_name = $implementer_declaring_method_id->fq_class_name;
                        $implementer_method_storage = $codebase->methods->getStorage(
                            $implementer_declaring_method_id
                        );
                        $implementer_classlike_storage = $classlike_storage_provider->get(
                            $implementer_fq_class_name
                        );
                    }

                    if (!$implementer_method_storage) {
                        if (IssueBuffer::accepts(
                            new UnimplementedInterfaceMethod(
                                'Method ' . $interface_method_name_lc . ' is not defined on class ' .
                                $storage->name,
                                $code_location
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    $implementer_appearing_method_id = $codebase->methods->getAppearingMethodId(
                        new \Psalm\Internal\MethodIdentifier(
                            $this->fq_class_name,
                            $interface_method_name_lc
                        )
                    );

                    $implementer_visibility = $implementer_method_storage->visibility;

                    if ($implementer_appearing_method_id
                        && $implementer_appearing_method_id !== $implementer_declaring_method_id
                    ) {
                        $appearing_fq_class_name = $implementer_appearing_method_id->fq_class_name;
                        $appearing_method_name = $implementer_appearing_method_id->method_name;

                        $appearing_class_storage = $classlike_storage_provider->get(
                            $appearing_fq_class_name
                        );

                        if (isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])) {
                            $implementer_visibility
                                = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
                        }
                    }

                    if ($implementer_visibility !== self::VISIBILITY_PUBLIC) {
                        if (IssueBuffer::accepts(
                            new InaccessibleMethod(
                                'Interface-defined method ' . $implementer_method_storage->cased_name
                                    . ' must be public in ' . $storage->name,
                                $code_location
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    if ($interface_method_storage->is_static && !$implementer_method_storage->is_static) {
                        if (IssueBuffer::accepts(
                            new MethodSignatureMismatch(
                                'Method ' . $implementer_method_storage->cased_name
                                . ' should be static like '
                                . $storage->name . '::' . $interface_method_storage->cased_name,
                                $code_location
                            ),
                            $implementer_method_storage->suppressed_issues
                        )) {
                            return false;
                        }
                    }

                    if ($storage->abstract && $implementer_method_storage === $interface_method_storage) {
                        continue;
                    }

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $implementer_classlike_storage ?: $storage,
                        $interface_storage,
                        $implementer_method_storage,
                        $interface_method_storage,
                        $this->fq_class_name,
                        $implementer_visibility,
                        $code_location,
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }
            }
        }

        if (!$class_context) {
            $class_context = new Context($this->fq_class_name);
            $class_context->parent = $parent_fq_class_name;
        }

        if ($global_context) {
            $class_context->strict_types = $global_context->strict_types;
        }

        if ($this->leftover_stmts) {
            (new StatementsAnalyzer(
                $this,
                new \Psalm\Internal\Provider\NodeDataProvider()
            ))->analyze(
                $this->leftover_stmts,
                $class_context
            );
        }

        if (!$storage->abstract) {
            foreach ($storage->declaring_method_ids as $declaring_method_id) {
                $method_storage = $codebase->methods->getStorage($declaring_method_id);

                $declaring_class_name = $declaring_method_id->fq_class_name;
                $method_name_lc = $declaring_method_id->method_name;

                if ($method_storage->abstract) {
                    if (IssueBuffer::accepts(
                        new UnimplementedAbstractMethod(
                            'Method ' . $method_name_lc . ' is not defined on class ' .
                            $this->fq_class_name . ', defined abstract in ' . $declaring_class_name,
                            new CodeLocation(
                                $this,
                                $class->name ? $class->name : $class,
                                $class_context->include_location,
                                true
                            )
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }
        }

        self::addContextProperties(
            $this,
            $storage,
            $class_context,
            $this->fq_class_name,
            $this->parent_fq_class_name,
            $class->stmts
        );

        $constructor_analyzer = null;
        $member_stmts = [];

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = $this->analyzeClassMethod(
                    $stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context
                );

                if ($stmt->name->name === '__construct') {
                    $constructor_analyzer = $method_analyzer;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                if ($this->analyzeTraitUse(
                    $this->source->getAliases(),
                    $stmt,
                    $project_analyzer,
                    $storage,
                    $class_context,
                    $global_context,
                    $constructor_analyzer
                ) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $member_stmts[] = $stmt;
                    }

                    if ($codebase->alter_code) {
                        $property_id = strtolower($this->fq_class_name) . '::$' . $prop->name;

                        $property_storage = $codebase->properties->getStorage($property_id);

                        if ($property_storage->type
                            && $property_storage->type_location
                            && $property_storage->type_location !== $property_storage->signature_type_location
                        ) {
                            $replace_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                $property_storage->type,
                                $this->getFQCLN(),
                                $this->getFQCLN(),
                                $this->getParentFQCLN()
                            );

                            $codebase->classlikes->handleDocblockTypeInMigration(
                                $codebase,
                                $this,
                                $replace_type,
                                $property_storage->type_location,
                                null
                            );
                        }

                        foreach ($codebase->properties_to_rename as $original_property_id => $new_property_name) {
                            if ($property_id === $original_property_id) {
                                $file_manipulations = [
                                    new \Psalm\FileManipulation(
                                        (int) $prop->name->getAttribute('startFilePos'),
                                        (int) $prop->name->getAttribute('endFilePos') + 1,
                                        '$' . $new_property_name
                                    )
                                ];

                                \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                                    $this->getFilePath(),
                                    $file_manipulations
                                );
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $member_stmts[] = $stmt;

                foreach ($stmt->consts as $const) {
                    $const_id = strtolower($this->fq_class_name) . '::' . $const->name;

                    foreach ($codebase->class_constants_to_rename as $original_const_id => $new_const_name) {
                        if ($const_id === $original_const_id) {
                            $file_manipulations = [
                                new \Psalm\FileManipulation(
                                    (int) $const->name->getAttribute('startFilePos'),
                                    (int) $const->name->getAttribute('endFilePos') + 1,
                                    $new_const_name
                                )
                            ];

                            \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                                $this->getFilePath(),
                                $file_manipulations
                            );
                        }
                    }
                }
            }
        }

        $statements_analyzer = new StatementsAnalyzer($this, new \Psalm\Internal\Provider\NodeDataProvider());
        $statements_analyzer->analyze($member_stmts, $class_context, $global_context, true);

        $config = Config::getInstance();

        $this->checkPropertyInitialization(
            $codebase,
            $config,
            $storage,
            $class_context,
            $global_context,
            $constructor_analyzer
        );

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property && !isset($stmt->type)) {
                $this->checkForMissingPropertyType($this, $stmt, $class_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    try {
                        $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_aliases = $trait_storage->aliases;

                    if ($trait_aliases === null) {
                        continue;
                    }

                    $trait_analyzer = new TraitAnalyzer(
                        $trait_node,
                        $trait_file_analyzer,
                        $fq_trait_name,
                        $trait_aliases
                    );

                    $fq_trait_name_lc = strtolower($fq_trait_name);

                    if (isset($storage->template_type_uses_count[$fq_trait_name_lc])) {
                        $expected_param_count = $storage->template_type_uses_count[$fq_trait_name_lc];

                        $this->checkTemplateParams(
                            $codebase,
                            $storage,
                            $trait_storage,
                            new CodeLocation(
                                $this,
                                $trait
                            ),
                            $expected_param_count
                        );
                    }

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\Property) {
                            $this->checkForMissingPropertyType($trait_analyzer, $trait_stmt, $class_context);
                        }
                    }

                    $trait_file_analyzer->clearSourceBeforeDestruction();
                }
            }
        }

        $pseudo_methods = $storage->pseudo_methods + $storage->pseudo_static_methods;

        foreach ($pseudo_methods as $pseudo_method_name => $pseudo_method_storage) {
            $pseudo_method_id = new \Psalm\Internal\MethodIdentifier(
                $this->fq_class_name,
                $pseudo_method_name
            );

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($pseudo_method_id);

            if ($overridden_method_ids
                && $pseudo_method_name !== '__construct'
                && $pseudo_method_storage->location
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    $overridden_fq_class_name = $overridden_method_id->fq_class_name;

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $storage,
                        $parent_storage,
                        $pseudo_method_storage,
                        $parent_method_storage,
                        $this->fq_class_name,
                        $pseudo_method_storage->visibility ?: 0,
                        $storage->location ?: $pseudo_method_storage->location,
                        $storage->suppressed_issues,
                        true,
                        false
                    );
                }
            }
        }

        $plugin_classes = $codebase->config->after_classlike_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterStatementAnalysis(
                    $class,
                    $storage,
                    $this,
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                    $this->getFilePath(),
                    $file_manipulations
                );
            }
        }

        return null;
    }

    public static function addContextProperties(
        StatementsSource $statements_source,
        ClassLikeStorage $storage,
        Context $class_context,
        string $fq_class_name,
        ?string $parent_fq_class_name,
        array $stmts = []
    ) : void {
        $codebase = $statements_source->getCodebase();

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id,
                true
            );

            if ($property_class_name === null) {
                continue;
            }

            $property_class_storage = $codebase->classlike_storage_provider->get($property_class_name);

            $property_storage = $property_class_storage->properties[$property_name];

            if (isset($storage->overridden_property_ids[$property_name])) {
                foreach ($storage->overridden_property_ids[$property_name] as $overridden_property_id) {
                    [$guide_class_name] = explode('::$', $overridden_property_id);
                    $guide_class_storage = $codebase->classlike_storage_provider->get($guide_class_name);
                    $guide_property_storage = $guide_class_storage->properties[$property_name];

                    if ($property_storage->visibility > $guide_property_storage->visibility
                        && $property_storage->location
                    ) {
                        if (IssueBuffer::accepts(
                            new OverriddenPropertyAccess(
                                'Property ' . $guide_class_storage->name . '::$' . $property_name
                                    . ' has different access level than '
                                    . $storage->name . '::$' . $property_name,
                                $property_storage->location
                            )
                        )) {
                            // fall through
                        }

                        continue;
                    }
                }
            }

            if ($property_storage->type) {
                $property_type = clone $property_storage->type;

                if (!$property_type->isMixed()
                    && !$property_storage->has_default
                    && !($property_type->isNullable() && $property_type->from_docblock)
                ) {
                    $property_type->initialized = false;
                }
            } else {
                $property_type = Type::getMixed();

                if (!$property_storage->has_default) {
                    $property_type->initialized = false;
                }
            }

            $property_type_location = $property_storage->type_location;

            $fleshed_out_type = !$property_type->isMixed()
                ? \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $property_type,
                    $fq_class_name,
                    $fq_class_name,
                    $parent_fq_class_name,
                    true,
                    false,
                    $storage->final
                )
                : $property_type;

            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $property_class_storage,
                $storage,
                null,
                new Type\Atomic\TNamedObject($fq_class_name),
                '$this'
            );

            $template_result = new \Psalm\Internal\Type\TemplateResult(
                $class_template_params ?: [],
                []
            );

            if ($class_template_params) {
                $fleshed_out_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                    $fleshed_out_type,
                    $template_result,
                    $codebase,
                    null,
                    null,
                    null,
                    $class_context->self
                );
            }

            if ($property_type_location && !$fleshed_out_type->isMixed()) {
                $stmt = array_filter($stmts, function ($stmt) use ($property_name): bool {
                    return $stmt instanceof PhpParser\Node\Stmt\Property
                        && isset($stmt->props[0]->name->name)
                        && $stmt->props[0]->name->name === $property_name;
                });

                $suppressed = [];
                if (count($stmt) > 0) {
                    /** @var PhpParser\Node\Stmt\Property $stmt */
                    $stmt = array_pop($stmt);

                    $docComment = $stmt->getDocComment();
                    if ($docComment) {
                        try {
                            $docBlock = DocComment::parsePreservingLength($docComment);
                            $suppressed = $docBlock->tags['psalm-suppress'] ?? [];
                        } catch (DocblockParseException $e) {
                            // do nothing to keep original behavior
                        }
                    }
                }

                $fleshed_out_type->check(
                    $statements_source,
                    $property_type_location,
                    $storage->suppressed_issues + $statements_source->getSuppressedIssues() + $suppressed,
                    [],
                    false
                );
            }

            if ($property_storage->is_static) {
                $property_id = $fq_class_name . '::$' . $property_name;

                $class_context->vars_in_scope[$property_id] = $fleshed_out_type;
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $fleshed_out_type;
            }
        }

        foreach ($storage->pseudo_property_get_types as $property_name => $property_type) {
            $property_name = substr($property_name, 1);

            if (isset($class_context->vars_in_scope['$this->' . $property_name])) {
                $fleshed_out_type = !$property_type->isMixed()
                    ? \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $property_type,
                        $fq_class_name,
                        $fq_class_name,
                        $parent_fq_class_name
                    )
                    : $property_type;

                $class_context->vars_in_scope['$this->' . $property_name] = $fleshed_out_type;
            }
        }
    }

    private function checkPropertyInitialization(
        Codebase $codebase,
        Config $config,
        ClassLikeStorage $storage,
        Context $class_context,
        ?Context $global_context = null,
        ?MethodAnalyzer $constructor_analyzer = null
    ): void {
        if (!$config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())) {
            return;
        }

        if (!isset($storage->declaring_method_ids['__construct'])
            && !$config->reportIssueInFile('MissingConstructor', $this->getFilePath())
        ) {
            return;
        }

        $fq_class_name = $class_context->self ? $class_context->self : $this->fq_class_name;
        $fq_class_name_lc = strtolower($fq_class_name);

        $included_file_path = $this->getFilePath();

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            $fq_class_name_lc . '::__construct',
            true
        );

        if ($method_already_analyzed && !$codebase->diff_methods) {
            // this can happen when re-analysing a class that has been include()d inside another
            return;
        }

        /** @var PhpParser\Node\Stmt\Class_ */
        $class = $this->class;
        $classlike_storage_provider = $codebase->classlike_storage_provider;
        $class_storage = $classlike_storage_provider->get($fq_class_name_lc);

        $constructor_appearing_fqcln = $fq_class_name_lc;

        $uninitialized_variables = [];
        $uninitialized_properties = [];
        $uninitialized_typed_properties = [];
        $uninitialized_private_properties = false;

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id,
                true
            );

            if ($property_class_name === null) {
                continue;
            }

            $property_class_storage = $classlike_storage_provider->get($property_class_name);

            $property = $property_class_storage->properties[$property_name];

            $property_is_initialized = isset($property_class_storage->initialized_properties[$property_name]);

            if ($property->is_static) {
                continue;
            }

            if ($property->has_default || $property_is_initialized) {
                continue;
            }

            if ($property->type && $property->type->isNullable() && $property->type->from_docblock) {
                continue;
            }

            if ($codebase->diff_methods && $method_already_analyzed && $property->location) {
                [$start, $end] = $property->location->getSelectionBounds();

                $existing_issues = $codebase->analyzer->getExistingIssuesForFile(
                    $this->getFilePath(),
                    $start,
                    $end,
                    'PropertyNotSetInConstructor'
                );

                if ($existing_issues) {
                    IssueBuffer::addIssues([$this->getFilePath() => $existing_issues]);
                    continue;
                }
            }

            if ($property->location) {
                $codebase->analyzer->removeExistingDataForFile(
                    $this->getFilePath(),
                    $property->location->raw_file_start,
                    $property->location->raw_file_end,
                    'PropertyNotSetInConstructor'
                );
            }

            $codebase->file_reference_provider->addMethodReferenceToMissingClassMember(
                $fq_class_name_lc . '::__construct',
                strtolower($property_class_name) . '::$' . $property_name
            );

            if ($property->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                $uninitialized_private_properties = true;
            }

            $uninitialized_variables[] = '$this->' . $property_name;
            $uninitialized_properties[$property_class_name . '::$' . $property_name] = $property;

            if ($property->type && !$property->type->isMixed()) {
                $uninitialized_typed_properties[$property_class_name . '::$' . $property_name] = $property;
            }
        }

        if (!$uninitialized_properties) {
            return;
        }

        if (!$storage->abstract
            && !$constructor_analyzer
            && isset($storage->declaring_method_ids['__construct'])
            && isset($storage->appearing_method_ids['__construct'])
            && $class->extends
        ) {
            $constructor_declaring_fqcln = $storage->declaring_method_ids['__construct']->fq_class_name;
            $constructor_appearing_fqcln = $storage->appearing_method_ids['__construct']->fq_class_name;

            $constructor_class_storage = $classlike_storage_provider->get($constructor_declaring_fqcln);

            // ignore oldstyle constructors and classes without any declared properties
            if ($constructor_class_storage->user_defined
                && !$constructor_class_storage->stubbed
                && isset($constructor_class_storage->methods['__construct'])
            ) {
                $constructor_storage = $constructor_class_storage->methods['__construct'];

                $fake_constructor_params = array_map(
                    function (FunctionLikeParameter $param) : PhpParser\Node\Param {
                        $fake_param = (new PhpParser\Builder\Param($param->name));
                        if ($param->signature_type) {
                            $fake_param->setType((string)$param->signature_type);
                        }

                        return $fake_param->getNode();
                    },
                    $constructor_storage->params
                );

                $fake_constructor_stmt_args = array_map(
                    function (FunctionLikeParameter $param) : PhpParser\Node\Arg {
                        return new PhpParser\Node\Arg(new PhpParser\Node\Expr\Variable($param->name));
                    },
                    $constructor_storage->params
                );

                $fake_constructor_stmts = [
                    new PhpParser\Node\Stmt\Expression(
                        new PhpParser\Node\Expr\StaticCall(
                            new PhpParser\Node\Name\FullyQualified($constructor_declaring_fqcln),
                            new PhpParser\Node\Identifier('__construct'),
                            $fake_constructor_stmt_args,
                            [
                                'startLine' => $class->extends->getLine(),
                                'startFilePos' => $class->extends->getAttribute('startFilePos'),
                                'endFilePos' => $class->extends->getAttribute('endFilePos'),
                                'comments' => [new PhpParser\Comment\Doc(
                                    '/** @psalm-suppress InaccessibleMethod */',
                                    $class->extends->getLine(),
                                    (int) $class->extends->getAttribute('startFilePos')
                                )],
                            ]
                        ),
                        [
                            'startLine' => $class->extends->getLine(),
                            'startFilePos' => $class->extends->getAttribute('startFilePos'),
                            'endFilePos' => $class->extends->getAttribute('endFilePos'),
                            'comments' => [new PhpParser\Comment\Doc(
                                '/** @psalm-suppress InaccessibleMethod */',
                                $class->extends->getLine(),
                                (int) $class->extends->getAttribute('startFilePos')
                            )],
                        ]
                    ),
                ];

                $fake_stmt = new PhpParser\Node\Stmt\ClassMethod(
                    new PhpParser\Node\Identifier('__construct'),
                    [
                        'type' => PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
                        'params' => $fake_constructor_params,
                        'stmts' => $fake_constructor_stmts,
                    ],
                    [
                        'startLine' => $class->extends->getLine(),
                        'startFilePos' => $class->extends->getAttribute('startFilePos'),
                        'endFilePos' => $class->extends->getAttribute('endFilePos'),
                    ]
                );

                $codebase->analyzer->disableMixedCounts();

                $was_collecting_initializations = $class_context->collect_initializations;

                $class_context->collect_initializations = true;
                $class_context->collect_nonprivate_initializations = !$uninitialized_private_properties;

                $constructor_analyzer = $this->analyzeClassMethod(
                    $fake_stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context,
                    true
                );

                $class_context->collect_initializations = $was_collecting_initializations;

                $codebase->analyzer->enableMixedCounts();
            }
        }

        if ($constructor_analyzer) {
            $method_context = clone $class_context;
            $method_context->collect_initializations = true;
            $method_context->collect_nonprivate_initializations = !$uninitialized_private_properties;
            $method_context->self = $fq_class_name;

            $this_atomic_object_type = new Type\Atomic\TNamedObject($fq_class_name);
            $this_atomic_object_type->was_static = !$storage->final;

            $method_context->vars_in_scope['$this'] = new Type\Union([$this_atomic_object_type]);
            $method_context->vars_possibly_in_scope['$this'] = true;
            $method_context->calling_method_id = strtolower($fq_class_name) . '::__construct';

            $constructor_analyzer->analyze(
                $method_context,
                new \Psalm\Internal\Provider\NodeDataProvider(),
                $global_context,
                true
            );

            foreach ($uninitialized_properties as $property_id => $property_storage) {
                [, $property_name] = explode('::$', $property_id);

                if (!isset($method_context->vars_in_scope['$this->' . $property_name])) {
                    $end_type = Type::getVoid();
                    $end_type->initialized = false;
                } else {
                    $end_type = $method_context->vars_in_scope['$this->' . $property_name];
                }

                $constructor_class_property_storage = $property_storage;

                $error_location = $property_storage->location;

                if ($storage->declaring_property_ids[$property_name] !== $fq_class_name) {
                    $error_location = $storage->location ?: $storage->stmt_location;
                }

                if ($fq_class_name_lc !== $constructor_appearing_fqcln
                    && $property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                ) {
                    $a_class_storage = $classlike_storage_provider->get(
                        $end_type->initialized_class ?: $constructor_appearing_fqcln
                    );

                    if (!isset($a_class_storage->declaring_property_ids[$property_name])) {
                        $constructor_class_property_storage = null;
                    } else {
                        $declaring_property_class = $a_class_storage->declaring_property_ids[$property_name];
                        $constructor_class_property_storage = $classlike_storage_provider
                            ->get($declaring_property_class)
                            ->properties[$property_name];
                    }
                }

                if ($property_storage->location
                    && $error_location
                    && (!$end_type->initialized || $property_storage !== $constructor_class_property_storage)
                ) {
                    if ($property_storage->type) {
                        $expected_visibility = $uninitialized_private_properties
                            ? 'private or final '
                            : '';

                        if (IssueBuffer::accepts(
                            new PropertyNotSetInConstructor(
                                'Property ' . $class_storage->name . '::$' . $property_name
                                    . ' is not defined in constructor of '
                                    . $this->fq_class_name . ' and in any ' . $expected_visibility
                                    . 'methods called in the constructor',
                                $error_location,
                                $property_id
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        )) {
                            // do nothing
                        }
                    } elseif (!$property_storage->has_default) {
                        if (isset($this->inferred_property_types[$property_name])) {
                            $this->inferred_property_types[$property_name]->addType(new Type\Atomic\TNull());
                            $this->inferred_property_types[$property_name]->setFromDocblock();
                        }
                    }
                }
            }

            $codebase->analyzer->setAnalyzedMethod(
                $included_file_path,
                $fq_class_name_lc . '::__construct',
                true
            );

            return;
        }

        if (!$storage->abstract && $uninitialized_typed_properties) {
            foreach ($uninitialized_typed_properties as $id => $uninitialized_property) {
                if ($uninitialized_property->location) {
                    if (IssueBuffer::accepts(
                        new MissingConstructor(
                            $class_storage->name . ' has an uninitialized property ' . $id .
                                ', but no constructor',
                            $uninitialized_property->location,
                            $class_storage->name . '::' . $uninitialized_variables[0]
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @return false|null
     */
    private function analyzeTraitUse(
        Aliases $aliases,
        PhpParser\Node\Stmt\TraitUse $stmt,
        ProjectAnalyzer $project_analyzer,
        ClassLikeStorage $storage,
        Context $class_context,
        ?Context $global_context = null,
        ?MethodAnalyzer &$constructor_analyzer = null,
        ?TraitAnalyzer $previous_trait_analyzer = null
    ): ?bool {
        $codebase = $this->getCodebase();

        $previous_context_include_location = $class_context->include_location;

        foreach ($stmt->traits as $trait_name) {
            $trait_location = new CodeLocation($this, $trait_name, null, true);
            $class_context->include_location = new CodeLocation($this, $trait_name, null, true);

            $fq_trait_name = self::getFQCLNFromNameObject(
                $trait_name,
                $aliases
            );

            if (!$codebase->classlikes->hasFullyQualifiedTraitName($fq_trait_name, $trait_location)) {
                if (IssueBuffer::accepts(
                    new UndefinedTrait(
                        'Trait ' . $fq_trait_name . ' does not exist',
                        new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues()
                )) {
                    // fall through
                }

                return false;
            } else {
                if (!$codebase->traitHasCorrectCase($fq_trait_name)) {
                    if (IssueBuffer::accepts(
                        new UndefinedTrait(
                            'Trait ' . $fq_trait_name . ' has wrong casing',
                            new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                $fq_trait_name_resolved = $codebase->classlikes->getUnAliasedName($fq_trait_name);
                $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name_resolved);

                if ($trait_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedTrait(
                            'Trait ' . $fq_trait_name . ' is deprecated',
                            new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($trait_storage->extension_requirement !== null) {
                    $extension_requirement = $codebase->classlikes->getUnAliasedName(
                        $trait_storage->extension_requirement
                    );
                    $extensionRequirementMet = in_array($extension_requirement, $storage->parent_classes);

                    if (!$extensionRequirementMet) {
                        if (IssueBuffer::accepts(
                            new ExtensionRequirementViolation(
                                $fq_trait_name . ' requires using class to extend ' . $extension_requirement
                                    . ', but ' . $storage->name . ' does not',
                                new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                foreach ($trait_storage->implementation_requirements as $implementation_requirement) {
                    $implementation_requirement = $codebase->classlikes->getUnAliasedName($implementation_requirement);
                    $implementationRequirementMet = in_array($implementation_requirement, $storage->class_implements);

                    if (!$implementationRequirementMet) {
                        if (IssueBuffer::accepts(
                            new ImplementationRequirementViolation(
                                $fq_trait_name . ' requires using class to implement '
                                    . $implementation_requirement . ', but ' . $storage->name . ' does not',
                                new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                            ),
                            $storage->suppressed_issues + $this->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if ($storage->mutation_free && !$trait_storage->mutation_free) {
                    if (IssueBuffer::accepts(
                        new MutableDependency(
                            $storage->name . ' is marked immutable but ' . $fq_trait_name . ' is not',
                            new CodeLocation($previous_trait_analyzer ?: $this, $trait_name)
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name_resolved);
                $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name_resolved);
                $trait_aliases = $trait_storage->aliases;
                if ($trait_aliases === null) {
                    continue;
                }

                $trait_analyzer = new TraitAnalyzer(
                    $trait_node,
                    $trait_file_analyzer,
                    $fq_trait_name_resolved,
                    $trait_aliases
                );

                foreach ($trait_node->stmts as $trait_stmt) {
                    if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                        $trait_method_analyzer = $this->analyzeClassMethod(
                            $trait_stmt,
                            $storage,
                            $trait_analyzer,
                            $class_context,
                            $global_context
                        );

                        if ($trait_stmt->name->name === '__construct') {
                            $constructor_analyzer = $trait_method_analyzer;
                        }
                    } elseif ($trait_stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                        if ($this->analyzeTraitUse(
                            $trait_aliases,
                            $trait_stmt,
                            $project_analyzer,
                            $storage,
                            $class_context,
                            $global_context,
                            $constructor_analyzer,
                            $trait_analyzer
                        ) === false) {
                            return false;
                        }
                    }
                }

                $trait_file_analyzer->clearSourceBeforeDestruction();
            }
        }

        $class_context->include_location = $previous_context_include_location;

        return null;
    }

    private function checkForMissingPropertyType(
        StatementsSource $source,
        PhpParser\Node\Stmt\Property $stmt,
        Context $context
    ): void {
        $fq_class_name = $source->getFQCLN();
        $property_name = $stmt->props[0]->name->name;

        $codebase = $this->getCodebase();

        $property_id = $fq_class_name . '::$' . $property_name;

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true
        );

        if (!$declaring_property_class) {
            return;
        }

        $fq_class_name = $declaring_property_class;

        // gets inherited property type
        $class_property_type = $codebase->properties->getPropertyType($property_id, false, $source, $context);

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $property_storage = $class_storage->properties[$property_name];

        if ($class_property_type && ($property_storage->type_location || !$codebase->alter_code)) {
            return;
        }

        $message = 'Property ' . $property_id . ' does not have a declared type';

        $suggested_type = $property_storage->suggested_type;

        if (isset($this->inferred_property_types[$property_name])) {
            $suggested_type = $suggested_type
                ? Type::combineUnionTypes(
                    $suggested_type,
                    $this->inferred_property_types[$property_name],
                    $codebase
                )
                : $this->inferred_property_types[$property_name];
        }

        if ($suggested_type && !$property_storage->has_default && $property_storage->is_static) {
            $suggested_type->addType(new Type\Atomic\TNull());
        }

        if ($suggested_type && !$suggested_type->isNull()) {
            $message .= ' - consider ' . str_replace(
                ['<array-key, mixed>', '<empty, empty>'],
                '',
                (string)$suggested_type
            );
        }

        $project_analyzer = ProjectAnalyzer::getInstance();

        if ($codebase->alter_code
            && $source === $this
            && isset($project_analyzer->getIssuesToFix()['MissingPropertyType'])
            && !\in_array('MissingPropertyType', $this->getSuppressedIssues())
            && $suggested_type
        ) {
            if ($suggested_type->hasMixed() || $suggested_type->isNull()) {
                return;
            }

            self::addOrUpdatePropertyType(
                $project_analyzer,
                $stmt,
                $suggested_type,
                $this,
                $suggested_type->from_docblock
            );

            return;
        }

        if (IssueBuffer::accepts(
            new MissingPropertyType(
                $message,
                new CodeLocation($source, $stmt->props[0]->name),
                $property_id
            ),
            $this->source->getSuppressedIssues()
        )) {
            // fall through
        }
    }

    private static function addOrUpdatePropertyType(
        ProjectAnalyzer $project_analyzer,
        PhpParser\Node\Stmt\Property $property,
        Type\Union $inferred_type,
        StatementsSource $source,
        bool $docblock_only = false
    ) : void {
        $manipulator = PropertyDocblockManipulator::getForProperty(
            $project_analyzer,
            $source->getFilePath(),
            $property
        );

        $codebase = $project_analyzer->getCodebase();

        $allow_native_type = !$docblock_only
            && $codebase->php_major_version >= 7
            && ($codebase->php_major_version > 7 || $codebase->php_minor_version >= 4)
            && $codebase->allow_backwards_incompatible_changes;

        $manipulator->setType(
            $allow_native_type
                ? (string) $inferred_type->toPhpString(
                    $source->getNamespace(),
                    $source->getAliasedClassesFlipped(),
                    $source->getFQCLN(),
                    $codebase->php_major_version,
                    $codebase->php_minor_version
                ) : null,
            $inferred_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                false
            ),
            $inferred_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                true
            ),
            $inferred_type->canBeFullyExpressedInPhp()
        );
    }

    private function analyzeClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        ClassLikeStorage $class_storage,
        SourceAnalyzer $source,
        Context $class_context,
        ?Context $global_context = null,
        bool $is_fake = false
    ): ?MethodAnalyzer {
        $config = Config::getInstance();

        if ($stmt->stmts === null && !$stmt->isAbstract()) {
            \Psalm\IssueBuffer::add(
                new \Psalm\Issue\ParseError(
                    'Non-abstract class method must have statements',
                    new CodeLocation($this, $stmt)
                )
            );

            return null;
        }

        try {
            $method_analyzer = new MethodAnalyzer($stmt, $source);
        } catch (\UnexpectedValueException $e) {
            \Psalm\IssueBuffer::add(
                new \Psalm\Issue\ParseError(
                    'Problem loading method: ' . $e->getMessage(),
                    new CodeLocation($this, $stmt)
                )
            );

            return null;
        }

        $actual_method_id = $method_analyzer->getMethodId();

        $project_analyzer = $source->getProjectAnalyzer();
        $codebase = $source->getCodebase();

        $analyzed_method_id = $actual_method_id;

        $included_file_path = $source->getFilePath();

        if ($class_context->self && strtolower($class_context->self) !== strtolower((string) $source->getFQCLN())) {
            $analyzed_method_id = $method_analyzer->getMethodId($class_context->self);

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ((string) $actual_method_id !== (string) $declaring_method_id) {
                // the method is an abstract trait method

                $declaring_method_storage = $method_analyzer->getFunctionLikeStorage();

                if (!$declaring_method_storage instanceof \Psalm\Storage\MethodStorage) {
                    throw new \LogicException('This should never happen');
                }

                if ($declaring_method_id && $declaring_method_storage->abstract) {
                    $implementer_method_storage = $codebase->methods->getStorage($declaring_method_id);
                    $declaring_storage = $codebase->classlike_storage_provider->get(
                        $actual_method_id->fq_class_name
                    );

                    MethodComparator::compare(
                        $codebase,
                        null,
                        $class_storage,
                        $declaring_storage,
                        $implementer_method_storage,
                        $declaring_method_storage,
                        $this->fq_class_name,
                        $implementer_method_storage->visibility,
                        new CodeLocation($source, $stmt),
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }

                return null;
            }
        }

        $trait_safe_method_id = strtolower((string) $analyzed_method_id);

        $actual_method_id_str = strtolower((string) $actual_method_id);

        if ($actual_method_id_str !== $trait_safe_method_id) {
            $trait_safe_method_id .= '&' . $actual_method_id_str;
        }

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            $trait_safe_method_id
        );

        $start = (int)$stmt->getAttribute('startFilePos');
        $end = (int)$stmt->getAttribute('endFilePos');

        $comments = $stmt->getComments();

        if ($comments) {
            $start = $comments[0]->getStartFilePos();
        }

        if ($codebase->diff_methods
            && $method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            $project_analyzer->progress->debug(
                'Skipping analysis of pre-analyzed method ' . $analyzed_method_id . "\n"
            );

            $existing_issues = $codebase->analyzer->getExistingIssuesForFile(
                $source->getFilePath(),
                $start,
                $end
            );

            IssueBuffer::addIssues([$source->getFilePath() => $existing_issues]);

            return $method_analyzer;
        }

        $codebase->analyzer->removeExistingDataForFile(
            $source->getFilePath(),
            $start,
            $end
        );

        $method_context = clone $class_context;
        foreach ($method_context->vars_in_scope as $context_var_id => $context_type) {
            $method_context->vars_in_scope[$context_var_id] = clone $context_type;
        }
        $method_context->collect_exceptions = $config->check_for_throws_docblock;

        $type_provider = new \Psalm\Internal\Provider\NodeDataProvider();

        $method_analyzer->analyze(
            $method_context,
            $type_provider,
            $global_context ? clone $global_context : null
        );

        if ($stmt->name->name !== '__construct'
            && $config->reportIssueInFile('InvalidReturnType', $source->getFilePath())
            && $class_context->self
        ) {
            self::analyzeClassMethodReturnType(
                $stmt,
                $method_analyzer,
                $source,
                $type_provider,
                $codebase,
                $class_storage,
                $class_context->self,
                $analyzed_method_id,
                $actual_method_id,
                $method_context->has_returned
            );
        }

        if (!$method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            $codebase->analyzer->setAnalyzedMethod($included_file_path, $trait_safe_method_id);
        }

        return $method_analyzer;
    }

    public static function analyzeClassMethodReturnType(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        MethodAnalyzer $method_analyzer,
        SourceAnalyzer $source,
        \Psalm\Internal\Provider\NodeDataProvider $type_provider,
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        string $fq_classlike_name,
        \Psalm\Internal\MethodIdentifier $analyzed_method_id,
        \Psalm\Internal\MethodIdentifier $actual_method_id,
        bool $did_explicitly_return
    ) : void {
        $secondary_return_type_location = null;

        $actual_method_storage = $codebase->methods->getStorage($actual_method_id);

        $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
            $actual_method_id,
            $secondary_return_type_location
        );

        $original_fq_classlike_name = $fq_classlike_name;

        $return_type = $codebase->methods->getMethodReturnType(
            $analyzed_method_id,
            $fq_classlike_name,
            $method_analyzer
        );

        if ($return_type && $class_storage->template_type_extends) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ($declaring_method_id) {
                $declaring_class_name = $declaring_method_id->fq_class_name;

                $class_storage = $codebase->classlike_storage_provider->get($declaring_class_name);
            }

            if ($class_storage->template_types) {
                $template_params = [];

                foreach ($class_storage->template_types as $param_name => $template_map) {
                    $key = array_keys($template_map)[0];

                    $template_params[] = new Type\Union([
                        new Type\Atomic\TTemplateParam(
                            $param_name,
                            \reset($template_map)[0],
                            $key
                        )
                    ]);
                }

                $this_object_type = new Type\Atomic\TGenericObject(
                    $original_fq_classlike_name,
                    $template_params
                );
            } else {
                $this_object_type = new Type\Atomic\TNamedObject($original_fq_classlike_name);
            }

            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $class_storage,
                $codebase->classlike_storage_provider->get($original_fq_classlike_name),
                strtolower($stmt->name->name),
                $this_object_type
            ) ?: [];

            $template_result = new \Psalm\Internal\Type\TemplateResult(
                $class_template_params ?: [],
                []
            );

            $return_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $return_type,
                $template_result,
                $codebase,
                null,
                null,
                null,
                $original_fq_classlike_name
            );
        }

        $overridden_method_ids = isset($class_storage->overridden_method_ids[strtolower($stmt->name->name)])
            ? $class_storage->overridden_method_ids[strtolower($stmt->name->name)]
            : [];

        if (!$return_type
            && !$class_storage->is_interface
            && $overridden_method_ids
        ) {
            foreach ($overridden_method_ids as $interface_method_id) {
                $interface_class = $interface_method_id->fq_class_name;

                if (!$codebase->classlikes->interfaceExists($interface_class)) {
                    continue;
                }

                $interface_return_type = $codebase->methods->getMethodReturnType(
                    $interface_method_id,
                    $interface_class
                );

                $interface_return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                    $interface_method_id
                );

                FunctionLike\ReturnTypeAnalyzer::verifyReturnType(
                    $stmt,
                    $stmt->getStmts() ?: [],
                    $source,
                    $type_provider,
                    $method_analyzer,
                    $interface_return_type,
                    $interface_class,
                    $interface_return_type_location,
                    [$analyzed_method_id],
                    $did_explicitly_return
                );
            }
        }

        if ($actual_method_storage->overridden_downstream) {
            $overridden_method_ids['overridden::downstream'] = 'overridden::downstream';
        }

        FunctionLike\ReturnTypeAnalyzer::verifyReturnType(
            $stmt,
            $stmt->getStmts() ?: [],
            $source,
            $type_provider,
            $method_analyzer,
            $return_type,
            $fq_classlike_name,
            $return_type_location,
            $overridden_method_ids,
            $did_explicitly_return
        );
    }

    private function checkTemplateParams(
        Codebase $codebase,
        ClassLikeStorage $storage,
        ClassLikeStorage $parent_storage,
        CodeLocation $code_location,
        int $expected_param_count
    ): void {
        $template_type_count = $parent_storage->template_types === null
            ? 0
            : count($parent_storage->template_types);

        if ($template_type_count > $expected_param_count) {
            if (IssueBuffer::accepts(
                new MissingTemplateParam(
                    $storage->name . ' has missing template params, expecting '
                        . $template_type_count,
                    $code_location
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            )) {
                // fall through
            }
        } elseif ($template_type_count < $expected_param_count) {
            if (IssueBuffer::accepts(
                new TooManyTemplateParams(
                    $storage->name . ' has too many template params, expecting '
                        . $template_type_count,
                    $code_location
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($parent_storage->template_types && $storage->template_type_extends) {
            $i = 0;

            $previous_extended = [];

            foreach ($parent_storage->template_types as $template_name => $type_map) {
                foreach ($type_map as $declaring_class => $template_type) {
                    if (isset($storage->template_type_extends[$parent_storage->name][$template_name])) {
                        $extended_type = $storage->template_type_extends[$parent_storage->name][$template_name];

                        if (isset($parent_storage->template_covariants[$i])
                            && !$parent_storage->template_covariants[$i]
                        ) {
                            foreach ($extended_type->getAtomicTypes() as $t) {
                                if ($t instanceof Type\Atomic\TTemplateParam
                                    && $storage->template_types
                                    && $storage->template_covariants
                                    && ($local_offset
                                        = array_search($t->param_name, array_keys($storage->template_types)))
                                        !== false
                                    && !empty($storage->template_covariants[$local_offset])
                                ) {
                                    if (IssueBuffer::accepts(
                                        new InvalidTemplateParam(
                                            'Cannot extend an invariant template param ' . $template_name
                                                . ' into a covariant context',
                                            $code_location
                                        ),
                                        $storage->suppressed_issues + $this->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }
                                }
                            }
                        }

                        if (!$template_type[0]->isMixed()) {
                            $template_type_copy = clone $template_type[0];

                            $template_result = new \Psalm\Internal\Type\TemplateResult(
                                $previous_extended ?: [],
                                []
                            );

                            $template_type_copy = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                                $template_type_copy,
                                $template_result,
                                $codebase,
                                null,
                                $extended_type,
                                null,
                                null
                            );

                            if (!UnionTypeComparator::isContainedBy($codebase, $extended_type, $template_type_copy)) {
                                if (IssueBuffer::accepts(
                                    new InvalidTemplateParam(
                                        'Extended template param ' . $template_name
                                            . ' expects type ' . $template_type_copy->getId()
                                            . ', type ' . $extended_type->getId() . ' given',
                                        $code_location
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } else {
                                $previous_extended[$template_name] = [
                                    $declaring_class => [$extended_type]
                                ];
                            }
                        } else {
                            $previous_extended[$template_name] = [
                                $declaring_class => [$extended_type]
                            ];
                        }
                    }
                }

                $i++;
            }
        }
    }
}
