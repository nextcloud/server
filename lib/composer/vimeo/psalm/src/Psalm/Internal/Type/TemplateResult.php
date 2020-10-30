<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;

class TemplateResult
{
    /**
     * @var array<string, array<string, array{0: Union}>>
     */
    public $template_types;

    /**
     * @var array<string, array<string, array{0: Union, 1?: int, 2?: ?int}>>
     */
    public $upper_bounds;

    /**
     * @var array<string, array<string, array{0: Union, 1?: int, 2?: ?int}>>
     */
    public $lower_bounds = [];

    /**
     * @var list<Union>
     */
    public $lower_bounds_unintersectable_types = [];

    /**
     * @param  array<string, array<string, array{0: Union}>> $template_types
     * @param  array<string, array<string, array{0: Union, 1?: int, 2?: ?int}>> $upper_bounds
     */
    public function __construct(array $template_types, array $upper_bounds)
    {
        $this->template_types = $template_types;
        $this->upper_bounds = $upper_bounds;
    }
}
