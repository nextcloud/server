<?php

declare(strict_types=1);

/**
 * @copyright 2020, Maxence Lange <maxence@artificial-owl.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\WellKnown\Model;

use JsonSerializable;
use OCP\IRequest;

/**
 * @since 21.0.0
 *
 * @package OCP\WellKnown\Model
 */
interface IWellKnown {


	/**
	 * @return string
	 * @since 21.0.0
	 */
	public function getService(): string;

	/**
	 * @param string $service
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isService(string $service): bool;


	/**
	 * @return IRequest
	 * @since 21.0.0
	 */
	public function getRequest(): IRequest;


	/**
	 * @return string
	 * @since 21.0.0
	 */
	public function getSubject(): string;

	/**
	 * @param string $subject
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isSubject(string $subject): bool;


	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getRels(): array;

	/**
	 * @param string $rel
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isRequestedRel(string $rel): bool;


	/**
	 * @param string $alias
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addAlias(string $alias): IWellKnown;

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getAliases(): array;


	/**
	 * @param string $property
	 * @param $value
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addProperty(string $property, $value): IWellKnown;

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getProperties(): array;


	/**
	 * Please refer to official RFC regarding the generation of $link:
	 * - https://tools.ietf.org/html/rfc7033#section-4.4.4
	 *
	 * @param array $link
	 *
	 * @psalm-param array{rel: string, type: string, href: string, titles: array, properties: array} $link
	 * @return IWellKnown
	 *
	 * @since 21.0.0
	 */
	public function addLink(array $link): IWellKnown;

	/**
	 * @param JsonSerializable $object
	 *
	 * @return IWellKnown
	 * @since 21.0.0
	 */
	public function addLinkSerialized(JsonSerializable $object): IWellKnown;

	/**
	 * @return array
	 * @since 21.0.0
	 */
	public function getLinks(): array;
}
