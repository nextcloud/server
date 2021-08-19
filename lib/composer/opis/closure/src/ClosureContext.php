<?php
/* ===========================================================================
 * Copyright (c) 2018-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure;

/**
 * Closure context class
 * @internal
 */
class ClosureContext
{
    /**
     * @var ClosureScope Closures scope
     */
    public $scope;

    /**
     * @var integer
     */
    public $locks;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->scope = new ClosureScope();
        $this->locks = 0;
    }
}