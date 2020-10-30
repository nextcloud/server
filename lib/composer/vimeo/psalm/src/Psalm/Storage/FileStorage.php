<?php
namespace Psalm\Storage;

use Psalm\Aliases;

class FileStorage
{
    use CustomMetadataTrait;

    /**
     * @var array<lowercase-string, string>
     */
    public $classlikes_in_file = [];

    /**
     * @var array<string>
     */
    public $referenced_classlikes = [];

    /**
     * @var array<string>
     */
    public $required_classes = [];

    /**
     * @var array<string>
     */
    public $required_interfaces = [];

    /**
     * @var bool
     */
    public $has_trait = false;

    /** @var string */
    public $file_path;

    /**
     * @var array<string, FunctionStorage>
     */
    public $functions = [];

    /** @var array<string, string> */
    public $declaring_function_ids = [];

    /**
     * @var array<string, \Psalm\Type\Union>
     */
    public $constants = [];

    /** @var array<string, string> */
    public $declaring_constants = [];

    /** @var array<string, string> */
    public $required_file_paths = [];

    /** @var array<string, string> */
    public $required_by_file_paths = [];

    /** @var bool */
    public $populated = false;

    /** @var bool */
    public $deep_scan = false;

    /** @var bool */
    public $has_extra_statements = false;

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var bool
     */
    public $has_visitor_issues = false;

    /**
     * @var list<\Psalm\Issue\CodeIssue>
     */
    public $docblock_issues = [];

    /**
     * @var array<string, \Psalm\Internal\Type\TypeAlias>
     */
    public $type_aliases = [];

    /**
     * @var array<lowercase-string, string>
     */
    public $classlike_aliases = [];

    /** @var ?Aliases */
    public $aliases;

    /** @var Aliases[] */
    public $namespace_aliases = [];

    public function __construct(string $file_path)
    {
        $this->file_path = $file_path;
    }
}
