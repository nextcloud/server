<?php
/* ===========================================================================
 * Copyright (c) 2018-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure;

use Closure;
use SuperClosure\Analyzer\ClosureAnalyzer;

/**
 * @deprecated We'll remove this class
 */
class Analyzer extends ClosureAnalyzer
{
    /**
     * Analyzer a given closure.
     *
     * @param Closure $closure
     *
     * @return array
     */
    public function analyze(Closure $closure)
    {
        $reflection = new ReflectionClosure($closure);
        $scope = $reflection->getClosureScopeClass();

        $data = [
            'reflection' => $reflection,
            'code'       => $reflection->getCode(),
            'hasThis'    => $reflection->isBindingRequired(),
            'context'    => $reflection->getUseVariables(),
            'hasRefs'    => false,
            'binding'    => $reflection->getClosureThis(),
            'scope'      => $scope ? $scope->getName() : null,
            'isStatic'   => $reflection->isStatic(),
        ];

        return $data;
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function determineCode(array &$data)
    {
        return null;
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function determineContext(array &$data)
    {
        return null;
    }

}
