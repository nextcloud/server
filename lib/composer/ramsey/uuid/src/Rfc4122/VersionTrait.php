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

/**
 * Provides common functionality for handling the version, as defined by RFC 4122
 *
 * @psalm-immutable
 */
trait VersionTrait
{
    /**
     * Returns the version
     */
    abstract public function getVersion(): ?int;

    /**
     * Returns true if these fields represent a nil UUID
     */
    abstract public function isNil(): bool;

    /**
     * Returns true if the version matches one of those defined by RFC 4122
     *
     * @return bool True if the UUID version is valid, false otherwise
     */
    private function isCorrectVersion(): bool
    {
        if ($this->isNil()) {
            return true;
        }

        switch ($this->getVersion()) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                return true;
        }

        return false;
    }
}
