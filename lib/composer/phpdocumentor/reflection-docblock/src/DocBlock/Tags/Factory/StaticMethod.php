<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection\DocBlock\Tags\Factory;

/**
 * @deprecated This contract is totally covered by Tag contract. Every class using StaticMethod also use Tag
 */
interface StaticMethod
{
    /**
     * @return mixed
     */
    public static function create(string $body);
}
