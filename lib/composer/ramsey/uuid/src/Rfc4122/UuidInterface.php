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

use Ramsey\Uuid\UuidInterface as BaseUuidInterface;

/**
 * Also known as a Leach-Salz variant UUID, an RFC 4122 variant UUID is a
 * universally unique identifier defined by RFC 4122
 *
 * @link https://tools.ietf.org/html/rfc4122 RFC 4122
 *
 * @psalm-immutable
 */
interface UuidInterface extends BaseUuidInterface
{
    /**
     * Returns the string standard representation of the UUID as a URN
     *
     * @link http://en.wikipedia.org/wiki/Uniform_Resource_Name Uniform Resource Name
     * @link https://tools.ietf.org/html/rfc4122#section-3 RFC 4122, § 3: Namespace Registration Template
     */
    public function getUrn(): string;
}
