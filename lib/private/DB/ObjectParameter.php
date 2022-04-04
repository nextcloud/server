<?php

declare(strict_types = 1);

/*
 * This file is part of the Symfony package.
 *
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @license AGPL-3.0-or-later AND MIT
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

namespace OC\DB;

final class ObjectParameter {
	private $object;
	private $error;
	private $stringable;
	private $class;

	/**
	 * @param object $object
	 */
	public function __construct($object, ?\Throwable $error) {
		$this->object = $object;
		$this->error = $error;
		$this->stringable = \is_callable([$object, '__toString']);
		$this->class = \get_class($object);
	}

	/**
	 * @return object
	 */
	public function getObject() {
		return $this->object;
	}

	public function getError(): ?\Throwable {
		return $this->error;
	}

	public function isStringable(): bool {
		return $this->stringable;
	}

	public function getClass(): string {
		return $this->class;
	}
}
