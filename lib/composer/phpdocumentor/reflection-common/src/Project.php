<?php

declare(strict_types=1);

/**
 * phpDocumentor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

/**
 * Interface for project. Since the definition of a project can be different per factory this interface will be small.
 */
interface Project
{
    /**
     * Returns the name of the project.
     */
    public function getName() : string;
}
