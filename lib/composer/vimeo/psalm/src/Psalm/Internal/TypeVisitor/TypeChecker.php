<?php
namespace Psalm\Internal\TypeVisitor;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TScalarClassConstant;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;
use function strtolower;

class TypeChecker extends NodeVisitor
{
    /**
     * @var StatementsSource
     */
    private $source;

    /**
     * @var CodeLocation
     */
    private $code_location;

    /**
     * @var array<string>
     */
    private $suppressed_issues;

    /**
     * @var array<string, bool>
     */
    private $phantom_classes;

    /**
     * @var bool
     */
    private $inferred;

    /**
     * @var bool
     */
    private $inherited;

    /**
     * @var bool
     */
    private $prevent_template_covariance;

    /** @var bool */
    private $has_errors = false;

    private $calling_method_id;

    /**
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     */
    public function __construct(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $inherited = false,
        bool $prevent_template_covariance = false,
        ?string $calling_method_id = null
    ) {
        $this->source = $source;
        $this->code_location = $code_location;
        $this->suppressed_issues = $suppressed_issues;
        $this->phantom_classes = $phantom_classes;
        $this->inferred = $inferred;
        $this->inherited = $inherited;
        $this->prevent_template_covariance = $prevent_template_covariance;
        $this->calling_method_id = $calling_method_id;
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param  \Psalm\Type\Atomic|\Psalm\Type\Union $type
     */
    protected function enterNode(TypeNode $type) : ?int
    {
        if ($type->checked) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TNamedObject) {
            $this->checkNamedObject($type);
        } elseif ($type instanceof TScalarClassConstant) {
            $this->checkScalarClassConstant($type);
        } elseif ($type instanceof TTemplateParam) {
            $this->checkTemplateParam($type);
        } elseif ($type instanceof TResource) {
            $this->checkResource($type);
        } elseif ($type instanceof TArray) {
            if (\count($type->type_params) > 2) {
                if (IssueBuffer::accepts(
                    new TooManyTemplateParams(
                        $type->getId(). ' has too many template params, expecting 2',
                        $this->code_location
                    ),
                    $this->suppressed_issues
                )) {
                    // fall through
                }
            }
        }

        $type->checked = true;

        return null;
    }

    public function hasErrors() : bool
    {
        return $this->has_errors;
    }

    private function checkNamedObject(TNamedObject $atomic) : void
    {
        $codebase = $this->source->getCodebase();

        if ($this->code_location instanceof CodeLocation\DocblockTypeLocation
            && $codebase->store_node_types
            && $atomic->offset_start !== null
            && $atomic->offset_end !== null
        ) {
            $codebase->analyzer->addOffsetReference(
                $this->source->getFilePath(),
                $this->code_location->raw_file_start + $atomic->offset_start,
                $this->code_location->raw_file_start + $atomic->offset_end,
                $atomic->value
            );
        }

        if (!isset($this->phantom_classes[\strtolower($atomic->value)]) &&
            ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $this->source,
                $atomic->value,
                $this->code_location,
                $this->source->getFQCLN(),
                $this->calling_method_id,
                $this->suppressed_issues,
                $this->inferred,
                false,
                true,
                $atomic->from_docblock
            ) === false
        ) {
            $this->has_errors = true;
            return;
        }

        $fq_class_name_lc = strtolower($atomic->value);

        if (!$this->inherited
            && $codebase->classlike_storage_provider->has($fq_class_name_lc)
            && $this->source->getFQCLN() !== $atomic->value
        ) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name_lc);

            if ($class_storage->deprecated) {
                if (IssueBuffer::accepts(
                    new DeprecatedClass(
                        'Class ' . $atomic->value . ' is marked as deprecated',
                        $this->code_location,
                        $atomic->value
                    ),
                    $this->source->getSuppressedIssues() + $this->suppressed_issues
                )) {
                    // fall through
                }
            }
        }

        if ($atomic instanceof TGenericObject) {
            $this->checkGenericParams($atomic);
        }
    }

    private function checkGenericParams(TGenericObject $atomic) : void
    {
        $codebase = $this->source->getCodebase();

        try {
            $class_storage = $codebase->classlike_storage_provider->get(strtolower($atomic->value));
        } catch (\InvalidArgumentException $e) {
            return;
        }

        $expected_type_params = $class_storage->template_types ?: [];
        $expected_param_covariants = $class_storage->template_covariants;

        $template_type_count = \count($expected_type_params);
        $template_param_count = \count($atomic->type_params);

        if ($template_type_count > $template_param_count) {
            if (IssueBuffer::accepts(
                new MissingTemplateParam(
                    $atomic->value . ' has missing template params, expecting '
                        . $template_type_count,
                    $this->code_location
                ),
                $this->suppressed_issues
            )) {
                // fall through
            }
        } elseif ($template_type_count < $template_param_count) {
            if (IssueBuffer::accepts(
                new TooManyTemplateParams(
                    $atomic->getId(). ' has too many template params, expecting '
                        . $template_type_count,
                    $this->code_location
                ),
                $this->suppressed_issues
            )) {
                // fall through
            }
        }

        foreach ($atomic->type_params as $i => $type_param) {
            $this->prevent_template_covariance = $this->source instanceof \Psalm\Internal\Analyzer\MethodAnalyzer
                && $this->source->getMethodName() !== '__construct'
                && empty($expected_param_covariants[$i]);

            if (isset(\array_values($expected_type_params)[$i])) {
                $expected_type_param = \reset(\array_values($expected_type_params)[$i])[0];

                $expected_type_param = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $expected_type_param,
                    $this->source->getFQCLN(),
                    $this->source->getFQCLN(),
                    $this->source->getParentFQCLN()
                );

                $template_name = \array_keys($expected_type_params)[$i];

                $type_param = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $type_param,
                    $this->source->getFQCLN(),
                    $this->source->getFQCLN(),
                    $this->source->getParentFQCLN()
                );

                if (!UnionTypeComparator::isContainedBy($codebase, $type_param, $expected_type_param)) {
                    if (IssueBuffer::accepts(
                        new InvalidTemplateParam(
                            'Extended template param ' . $template_name
                                . ' of ' . $atomic->getId()
                                . ' expects type '
                                . $expected_type_param->getId()
                                . ', type ' . $type_param->getId() . ' given',
                            $this->code_location
                        ),
                        $this->suppressed_issues
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    public function checkScalarClassConstant(TScalarClassConstant $atomic) : void
    {
        $fq_classlike_name = $atomic->fq_classlike_name === 'self'
            ? $this->source->getClassName()
            : $atomic->fq_classlike_name;

        if (!$fq_classlike_name) {
            return;
        }

        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $this->source,
            $fq_classlike_name,
            $this->code_location,
            null,
            null,
            $this->suppressed_issues,
            $this->inferred,
            false,
            true,
            $atomic->from_docblock
        ) === false
        ) {
            $this->has_errors = true;
            return;
        }

        $const_name = $atomic->const_name;
        if (\strpos($const_name, '*') !== false) {
            $expanded = TypeExpander::expandAtomic(
                $this->source->getCodebase(),
                $atomic,
                $fq_classlike_name,
                $fq_classlike_name,
                null,
                true,
                true
            );

            $is_defined = \is_array($expanded) && \count($expanded) > 0;
        } else {
            $class_constant_type = $this->source->getCodebase()->classlikes->getClassConstantType(
                $fq_classlike_name,
                $atomic->const_name,
                \ReflectionProperty::IS_PRIVATE,
                null
            );

            $is_defined = null !== $class_constant_type;
        }

        if (!$is_defined) {
            if (\Psalm\IssueBuffer::accepts(
                new UndefinedConstant(
                    'Constant ' . $fq_classlike_name . '::' . $const_name . ' is not defined',
                    $this->code_location
                ),
                $this->source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }

    public function checkTemplateParam(\Psalm\Type\Atomic\TTemplateParam $atomic) : void
    {
        if ($this->prevent_template_covariance
            && \substr($atomic->defining_class, 0, 3) !== 'fn-'
        ) {
            $codebase = $this->source->getCodebase();

            $class_storage = $codebase->classlike_storage_provider->get($atomic->defining_class);

            $template_offset = $class_storage->template_types
                ? \array_search($atomic->param_name, \array_keys($class_storage->template_types), true)
                : false;

            if ($template_offset !== false
                && isset($class_storage->template_covariants[$template_offset])
                && $class_storage->template_covariants[$template_offset]
            ) {
                $method_storage = $this->source instanceof \Psalm\Internal\Analyzer\MethodAnalyzer
                    ? $this->source->getFunctionLikeStorage()
                    : null;

                if ($method_storage instanceof MethodStorage
                    && $method_storage->mutation_free
                    && !$method_storage->mutation_free_inferred
                ) {
                    // do nothing
                } else {
                    if (\Psalm\IssueBuffer::accepts(
                        new \Psalm\Issue\InvalidTemplateParam(
                            'Template param ' . $atomic->param_name . ' of '
                                . $atomic->defining_class . ' is marked covariant and cannot be used here',
                            $this->code_location
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    public function checkResource(TResource $atomic) : void
    {
        if (!$atomic->from_docblock) {
            if (\Psalm\IssueBuffer::accepts(
                new \Psalm\Issue\ReservedWord(
                    '\'resource\' is a reserved word',
                    $this->code_location,
                    'resource'
                ),
                $this->source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }
}
