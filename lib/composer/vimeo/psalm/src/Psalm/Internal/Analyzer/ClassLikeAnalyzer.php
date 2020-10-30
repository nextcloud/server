<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\MissingDependency;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use function strtolower;
use function preg_replace;
use function in_array;
use function preg_match;
use function explode;
use function array_pop;
use function implode;
use function gettype;

/**
 * @internal
 */
abstract class ClassLikeAnalyzer extends SourceAnalyzer
{
    public const VISIBILITY_PUBLIC = 1;
    public const VISIBILITY_PROTECTED = 2;
    public const VISIBILITY_PRIVATE = 3;

    public const SPECIAL_TYPES = [
        'int' => 'int',
        'string' => 'string',
        'float' => 'float',
        'bool' => 'bool',
        'false' => 'false',
        'object' => 'object',
        'empty' => 'empty',
        'callable' => 'callable',
        'array' => 'array',
        'iterable' => 'iterable',
        'null' => 'null',
        'mixed' => 'mixed',
    ];

    public const GETTYPE_TYPES = [
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'resource' => true,
        'resource (closed)' => true,
        'NULL' => true,
        'unknown type' => true,
    ];

    /**
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

    /**
     * @var StatementsSource
     */
    protected $source;

    /** @var FileAnalyzer */
    public $file_analyzer;

    /**
     * @var string
     */
    protected $fq_class_name;

    /**
     * The parent class
     *
     * @var string|null
     */
    protected $parent_fq_class_name;

    /**
     * @var PhpParser\Node\Stmt[]
     */
    protected $leftover_stmts = [];

    /** @var ClassLikeStorage */
    protected $storage;

    public function __construct(PhpParser\Node\Stmt\ClassLike $class, SourceAnalyzer $source, string $fq_class_name)
    {
        $this->class = $class;
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
    }

    public function __destruct()
    {
        $this->source = null;
        $this->file_analyzer = null;
    }

    public function getMethodMutations(
        string $method_name,
        Context $context
    ): void {
        $project_analyzer = $this->getFileAnalyzer()->project_analyzer;
        $codebase = $project_analyzer->getCodebase();

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $method_analyzer->analyze($context, new \Psalm\Internal\Provider\NodeDataProvider(), null, true);

                $context->clauses = [];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name);
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

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                            strtolower($trait_stmt->name->name) === strtolower($method_name)
                        ) {
                            $method_analyzer = new MethodAnalyzer($trait_stmt, $trait_analyzer);

                            $actual_method_id = $method_analyzer->getMethodId();

                            if ($context->self && $context->self !== $this->fq_class_name) {
                                $analyzed_method_id = $method_analyzer->getMethodId($context->self);
                                $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

                                if ((string) $actual_method_id !== (string) $declaring_method_id) {
                                    break;
                                }
                            }

                            $method_analyzer->analyze(
                                $context,
                                new \Psalm\Internal\Provider\NodeDataProvider(),
                                null,
                                true
                            );
                        }
                    }

                    $trait_file_analyzer->clearSourceBeforeDestruction();
                }
            }
        }
    }

    public function getFunctionLikeAnalyzer(string $method_name) : ?MethodAnalyzer
    {
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                return new MethodAnalyzer($stmt, $this);
            }
        }

        return null;
    }

    /**
     * @param  array<string>    $suppressed_issues
     * @param  bool             $inferred - whether or not the type was inferred
     */
    public static function checkFullyQualifiedClassLikeName(
        StatementsSource $statements_source,
        string $fq_class_name,
        CodeLocation $code_location,
        ?string $calling_fq_class_name,
        ?string $calling_method_id,
        array $suppressed_issues,
        bool $inferred = true,
        bool $allow_trait = false,
        bool $allow_interface = true,
        bool $from_docblock = false
    ): ?bool {
        $codebase = $statements_source->getCodebase();
        if ($fq_class_name === '') {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class or interface <empty string> does not exist',
                    $code_location,
                    'empty string'
                ),
                $suppressed_issues
            )) {
                return false;
            }

            return null;
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable', 'self', 'static', 'parent'], true)) {
            return true;
        }

        if (preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|object|mixed)$/i',
            $fq_class_name
        ) || strtolower($fq_class_name) === 'resource'
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (IssueBuffer::accepts(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    $code_location,
                    $class_name
                ),
                $suppressed_issues
            )) {
                // fall through
            }

            return null;
        }

        $class_exists = $codebase->classlikes->classExists(
            $fq_class_name,
            !$inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id
        );
        $interface_exists = $codebase->classlikes->interfaceExists(
            $fq_class_name,
            !$inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id
        );

        if (!$class_exists && !$interface_exists) {
            if (!$allow_trait || !$codebase->classlikes->traitExists($fq_class_name, $code_location)) {
                if ($from_docblock) {
                    if (IssueBuffer::accepts(
                        new UndefinedDocblockClass(
                            'Docblock-defined class or interface ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Class or interface ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            }

            return null;
        } elseif ($interface_exists && !$allow_interface) {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class ' . $fq_class_name . ' does not exist',
                    $code_location,
                    $fq_class_name
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        $aliased_name = $codebase->classlikes->getUnAliasedName(
            $fq_class_name
        );

        try {
            $class_storage = $codebase->classlike_storage_provider->get($aliased_name);
        } catch (\InvalidArgumentException $e) {
            if (!$inferred) {
                throw $e;
            }

            return null;
        }

        foreach ($class_storage->invalid_dependencies as $dependency_class_name) {
            // if the implemented/extended class is stubbed, it may not yet have
            // been hydrated
            if ($codebase->classlike_storage_provider->has($dependency_class_name)) {
                continue;
            }

            if (IssueBuffer::accepts(
                new MissingDependency(
                    $fq_class_name . ' depends on class or interface '
                        . $dependency_class_name . ' that does not exist',
                    $code_location,
                    $fq_class_name
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        if (!$inferred) {
            if (($class_exists && !$codebase->classHasCorrectCasing($fq_class_name)) ||
                ($interface_exists && !$codebase->interfaceHasCorrectCasing($fq_class_name))
            ) {
                if ($codebase->classlikes->isUserDefined(strtolower($aliased_name))) {
                    if (IssueBuffer::accepts(
                        new InvalidClass(
                            'Class or interface ' . $fq_class_name . ' has wrong casing',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        // fall through here
                    }
                }
            }
        }

        if (!$inferred) {
            $plugin_classes = $codebase->config->after_classlike_exists_checks;

            if ($plugin_classes) {
                $file_manipulations = [];

                foreach ($plugin_classes as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterClassLikeExistenceCheck(
                        $fq_class_name,
                        $code_location,
                        $statements_source,
                        $codebase,
                        $file_manipulations
                    );
                }

                if ($file_manipulations) {
                    FileManipulationBuffer::add($code_location->file_path, $file_manipulations);
                }
            }
        }

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     *
     */
    public static function getFQCLNFromNameObject(
        PhpParser\Node\Name $class_name,
        Aliases $aliases
    ): string {
        /** @var string|null */
        $resolved_name = $class_name->getAttribute('resolvedName');

        if ($resolved_name) {
            return $resolved_name;
        }

        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        if (in_array($class_name->parts[0], ['self', 'static', 'parent'], true)) {
            return $class_name->parts[0];
        }

        return Type::getFQCLNFromString(
            implode('\\', $class_name->parts),
            $aliases
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlippedReplaceable();
        }

        return [];
    }

    public function getFQCLN(): string
    {
        return $this->fq_class_name;
    }

    public function getClassName(): ?string
    {
        return $this->class->name ? $this->class->name->name : null;
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap(): ?array
    {
        return $this->storage->template_types;
    }

    public function getParentFQCLN(): ?string
    {
        return $this->parent_fq_class_name;
    }

    public function isStatic(): bool
    {
        return false;
    }

    /**
     * Gets the Psalm type from a particular value
     *
     * @param  mixed $value
     *
     */
    public static function getTypeFromValue($value): Type\Union
    {
        switch (gettype($value)) {
            case 'boolean':
                if ($value) {
                    return Type::getTrue();
                }

                return Type::getFalse();

            case 'integer':
                return Type::getInt(false, $value);

            case 'double':
                return Type::getFloat($value);

            case 'string':
                return Type::getString($value);

            case 'array':
                return Type::getArray();

            case 'NULL':
                return Type::getNull();

            default:
                return Type::getMixed();
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    public static function checkPropertyVisibility(
        string $property_id,
        Context $context,
        SourceAnalyzer $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        bool $emit_issues = true
    ): ?bool {
        [$fq_class_name, $property_name] = explode('::$', $property_id);

        $codebase = $source->getCodebase();

        if ($codebase->properties->property_visibility_provider->has($fq_class_name)) {
            $property_visible = $codebase->properties->property_visibility_provider->isPropertyVisible(
                $source,
                $fq_class_name,
                $property_name,
                true,
                $context,
                $code_location
            );

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true
        );
        $appearing_property_class = $codebase->properties->getAppearingClassForProperty(
            $property_id,
            true
        );

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new \UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id
            );
        }

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $context->self) {
            return $emit_issues ? null : true;
        }

        if ($source->getSource() instanceof TraitAnalyzer
            && strtolower($declaring_property_class) === strtolower((string) $source->getFQCLN())
        ) {
            return $emit_issues ? null : true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);

        if (!isset($class_storage->properties[$property_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        $storage = $class_storage->properties[$property_name];

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return $emit_issues ? null : true;

            case self::VISIBILITY_PRIVATE:
                if (!$context->self || $appearing_property_class !== $context->self) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }

                return $emit_issues ? null : true;

            case self::VISIBILITY_PROTECTED:
                if ($appearing_property_class === $context->self) {
                    return null;
                }

                if (!$context->self) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }

                if ($codebase->classExtends($appearing_property_class, $context->self)) {
                    return $emit_issues ? null : true;
                }

                if (!$codebase->classExtends($context->self, $appearing_property_class)) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id . ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }
        }

        return $emit_issues ? null : true;
    }

    /**
     * @return  array<string, string>
     */
    public static function getClassesForFile(Codebase $codebase, string $file_path): array
    {
        try {
            return $codebase->file_storage_provider->get($file_path)->classlikes_in_file;
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this->file_analyzer;
    }
}
