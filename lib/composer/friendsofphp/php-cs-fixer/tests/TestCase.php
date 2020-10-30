<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnitGoodPractices\Traits\ExpectationViaCodeOverAnnotationTrait;
use PHPUnitGoodPractices\Traits\ExpectOverSetExceptionTrait;
use PHPUnitGoodPractices\Traits\IdentityOverEqualityTrait;
use PHPUnitGoodPractices\Traits\ProphecyOverMockObjectTrait;
use PHPUnitGoodPractices\Traits\ProphesizeOnlyInterfaceTrait;

if (trait_exists(ProphesizeOnlyInterfaceTrait::class)) {
    /**
     * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
     *
     * @internal
     */
    abstract class TestCase extends BaseTestCase
    {
        use ExpectationViaCodeOverAnnotationTrait;
        use ExpectOverSetExceptionTrait;
        use IdentityOverEqualityTrait;
        use ProphecyOverMockObjectTrait;
        use ProphesizeOnlyInterfaceTrait;
    }
} else {
    /**
     * Version without traits for cases when this class is used as a lib.
     *
     * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
     *
     * @internal
     *
     * @todo 3.0 To be removed when we clean up composer prod-autoloader from dev-packages.
     */
    abstract class TestCase extends BaseTestCase
    {
    }
}
