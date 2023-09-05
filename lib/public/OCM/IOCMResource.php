<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OCP\OCM;

/**
 * Model based on the Open Cloud Mesh Discovery API
 *
 * @link https://github.com/cs3org/OCM-API/
 * @since 28.0.0
 */
interface IOCMResource {
	/**
	 * set name of the resource
	 *
	 * @param string $name
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setName(string $name): self;

	/**
	 * get name of the resource
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getName(): string;

	/**
	 * set share types
	 *
	 * @param string[] $shareTypes
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setShareTypes(array $shareTypes): self;

	/**
	 * get share types
	 *
	 * @return string[]
	 * @since 28.0.0
	 */
	public function getShareTypes(): array;

	/**
	 * set available protocols
	 *
	 * @param array<string, string> $protocols
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setProtocols(array $protocols): self;

	/**
	 * get configured protocols
	 *
	 * @return array<string, string>
	 * @since 28.0.0
	 */
	public function getProtocols(): array;

	/**
	 * import data from an array
	 *
	 * @param array $data
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function import(array $data): self;
}
