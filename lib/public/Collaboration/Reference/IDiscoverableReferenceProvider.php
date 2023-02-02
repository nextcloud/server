<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
 */

namespace OCP\Collaboration\Reference;

/**
 * @since 26.0.0
 */
interface IDiscoverableReferenceProvider extends IReferenceProvider {
	/**
	 * @return string Unique id that identifies the reference provider
	 * @since 26.0.0
	 */
	public function getId(): string;

	/**
	 * @return string User facing title of the widget
	 * @since 26.0.0
	 */
	public function getTitle(): string;

	/**
	 * @return int Initial order for reference provider sorting
	 * @since 26.0.0
	 */
	public function getOrder(): int;

	/**
	 * @return string url to an icon that can be displayed next to the reference provider title
	 * @since 26.0.0
	 */
	public function getIconUrl(): string;

	/**
	 * @return array representation of the provider
	 * @since 26.0.0
	 */
	public function jsonSerialize(): array;
}
