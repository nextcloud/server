<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Ferdinand Thiessen <rpm@fthiessen.de>
 *
 * @author Ferdinand Thiessen <rpm@fthiessen.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\AppFramework\Utility;

use OCP\AppFramework\QueryException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Private implementation of the `Psr\Container\NotFoundExceptionInterface`
 *
 * QueryNotFoundException is a simple wrapper over the `QueryException`
 * to fulfill the PSR Container interface.
 *
 * You should not catch this class directly but the `NotFoundExceptionInterface`.
 */
class QueryNotFoundException extends QueryException implements NotFoundExceptionInterface {
}
