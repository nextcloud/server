<?php
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

use JsonSerializable;

/**
 * @since 25.0.0
 */
interface IReference extends JsonSerializable {

	/**
	 * @since 25.0.0
	 */
	public function getId(): string;

	/**
	 * @since 25.0.0
	 */
	public function setAccessible(bool $accessible): void;

	/**
	 * @since 25.0.0
	 */
	public function setTitle(string $title): void;

	/**
	 * @since 25.0.0
	 */
	public function getTitle(): string;

	/**
	 * @since 25.0.0
	 */
	public function setDescription(?string $description): void;

	/**
	 * @since 25.0.0
	 */
	public function getDescription(): ?string;

	/**
	 * @since 25.0.0
	 */
	public function setImageUrl(?string $imageUrl): void;

	/**
	 * @since 25.0.0
	 */
	public function getImageUrl(): ?string;

	/**
	 * @since 25.0.0
	 */
	public function setImageContentType(?string $contentType): void;

	/**
	 * @since 25.0.0
	 */
	public function getImageContentType(): ?string;

	/**
	 * @since 25.0.0
	 */
	public function setUrl(?string $url): void;

	/**
	 * @since 25.0.0
	 */
	public function getUrl(): ?string;

	/**
	 * @since 25.0.0
	 */
	public function setRichObject(string $type, array $richObject): void;

	/**
	 * @since 25.0.0
	 */
	public function getRichObjectType(): string;

	/**
	 * @since 25.0.0
	 */
	public function getRichObject(): array;

	/**
	 * @since 25.0.0
	 */
	public function getOpenGraphObject(): array;

	/**
	 * @since 25.0.0
	 */
	public static function toCache(IReference $reference): array;

	/**
	 * @since 25.0.0
	 */
	public static function fromCache(array $cache): IReference;
}
