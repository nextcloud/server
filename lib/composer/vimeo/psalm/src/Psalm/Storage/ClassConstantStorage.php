<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type;

class ClassConstantStorage
{
    /**
     * @var ?Type\Union
     */
    public $type;

    /**
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public $visibility = 1;

    /**
     * @var ?CodeLocation
     */
    public $location;

    /**
     * @var ?CodeLocation
     */
    public $stmt_location;

    /**
     * @var ?\Psalm\Internal\Scanner\UnresolvedConstantComponent
     */
    public $unresolved_node = null;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @param ClassLikeAnalyzer::VISIBILITY_* $visibility
     */
    public function __construct(?Type\Union $type, int $visibility, ?CodeLocation $location)
    {
        $this->visibility = $visibility;
        $this->location = $location;
        $this->type = $type;
    }
}
