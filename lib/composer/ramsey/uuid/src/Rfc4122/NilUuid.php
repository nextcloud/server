<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Rfc4122;

use Ramsey\Uuid\Uuid;

/**
 * The nil UUID is special form of UUID that is specified to have all 128 bits
 * set to zero
 *
 * @psalm-immutable
 */
final class NilUuid extends Uuid implements UuidInterface
{
}
