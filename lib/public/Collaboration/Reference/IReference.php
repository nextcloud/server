<?php

declare(strict_types=1);
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
	 * Accessible flag indicates if the user has access to the provided reference
	 *
	 * @since 25.0.0
	 */
	public function setAccessible(bool $accessible): void;

	/**
	 * Accessible flag indicates if the user has access to the provided reference
	 *
	 * @since 25.0.0
	 */
	public function getAccessible(): bool;

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
	public function getUrl(): string;

	/**
	 * Set the reference specific rich object representation
	 *
	 * @since 25.0.0
	 */
	public function setRichObject(string $type, ?array $richObject): void;

	/**
	 * Returns the type of the reference specific rich object
	 *
	 * @since 25.0.0
	 */
	public function getRichObjectType(): string;

	/**
	 * Returns the reference specific rich object representation
	 *
	 * @since 25.0.0
	 */
	public function getRichObject(): array;

	/**
	 * Returns the opengraph rich object representation
	 *
	 * @since 25.0.0
	 */
	public function getOpenGraphObject(): array;

	/**
	 * @return array{richObjectType: string, richObject: array<string, mixed>, openGraphObject: array{id: string, name: string, description: ?string, thumb: ?string, link: string}, accessible: bool}
	 *
	 * @since 25.0.0
	 */
	public function jsonSerialize(): array;
}
