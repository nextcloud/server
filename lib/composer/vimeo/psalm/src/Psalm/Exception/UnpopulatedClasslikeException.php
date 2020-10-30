<?php
namespace Psalm\Exception;

class UnpopulatedClasslikeException extends \LogicException
{
    public function __construct(string $fq_classlike_name)
    {
        parent::__construct(
            'Cannot check inheritance - \'' . $fq_classlike_name . '\' has not been populated yet.'
            . ' You may need to defer this check to a later phase.'
        );
    }
}
