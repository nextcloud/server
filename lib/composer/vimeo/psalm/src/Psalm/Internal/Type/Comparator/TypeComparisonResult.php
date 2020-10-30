<?php

namespace Psalm\Internal\Type\Comparator;

class TypeComparisonResult
{
    /** @var ?bool */
    public $scalar_type_match_found = null;

    /** @var ?bool */
    public $type_coerced = null;

    /** @var ?bool */
    public $type_coerced_from_mixed = null;

    /** @var ?bool */
    public $type_coerced_from_as_mixed = null;

    /** @var ?bool */
    public $to_string_cast = null;

    /** @var ?bool */
    public $type_coerced_from_scalar = null;

    /** @var ?\Psalm\Type\Union */
    public $replacement_union_type = null;

    /** @var ?\Psalm\Type\Atomic */
    public $replacement_atomic_type = null;
}
