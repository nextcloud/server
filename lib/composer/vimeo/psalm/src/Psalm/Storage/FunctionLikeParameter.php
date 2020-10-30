<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;

class FunctionLikeParameter
{
    use CustomMetadataTrait;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $by_ref;

    /**
     * @var Type\Union|null
     */
    public $type;

    /**
     * @var Type\Union|null
     */
    public $out_type;

    /**
     * @var Type\Union|null
     */
    public $signature_type;

    /**
     * @var bool
     */
    public $has_docblock_type = false;

    /**
     * @var bool
     */
    public $is_optional;

    /**
     * @var bool
     */
    public $is_nullable;

    /**
     * @var Type\Union|null
     */
    public $default_type;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $type_location;

    /**
     * @var CodeLocation|null
     */
    public $signature_type_location;

    /**
     * @var bool
     */
    public $is_variadic;

    /**
     * @var array<string>|null
     */
    public $sinks;

    /**
     * @var bool
     */
    public $assert_untainted = false;

    /**
     * @var bool
     */
    public $type_inferred = false;

    /**
     * @var bool
     */
    public $expect_variable = false;

    public function __construct(
        string $name,
        bool $by_ref,
        ?Type\Union $type = null,
        ?CodeLocation $location = null,
        ?CodeLocation $type_location = null,
        bool $is_optional = true,
        bool $is_nullable = false,
        bool $is_variadic = false,
        ?Type\Union $default_type = null
    ) {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->signature_type = $type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
        $this->is_variadic = $is_variadic;
        $this->location = $location;
        $this->type_location = $type_location;
        $this->signature_type_location = $type_location;
        $this->default_type = $default_type;
    }

    public function getId() : string
    {
        return ($this->type ? $this->type->getId() : 'mixed')
            . ($this->is_variadic ? '...' : '')
            . ($this->is_optional ? '=' : '');
    }

    public function __clone()
    {
        if ($this->type) {
            $this->type = clone $this->type;
        }
    }
}
