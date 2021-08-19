<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\Exceptions;

use League\Uri\Contracts\UriException;

class TemplateCanNotBeExpanded extends \InvalidArgumentException implements UriException
{
    public static function dueToUnableToProcessValueListWithPrefix(string $variableName): self
    {
        return new self('The ":" modifier can not be applied on "'.$variableName.'" since it is a list of values.');
    }

    public static function dueToNestedListOfValue(string $variableName): self
    {
        return new self('The "'.$variableName.'" can not be a nested list.');
    }
}
