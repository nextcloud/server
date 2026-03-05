<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
