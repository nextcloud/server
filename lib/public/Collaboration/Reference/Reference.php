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

/**
 * @since 25.0.0
 * @psalm-type OpenGraphObject = array{id: string, name: string, description: ?string, thumb: ?string, link: string}
 */
class Reference implements IReference {
	protected string $reference;

	protected bool $accessible = true;

	protected ?string $title = null;
	protected ?string $description = null;
	protected ?string $imageUrl = null;
	protected ?string $contentType = null;
	protected ?string $url = null;

	protected ?string $richObjectType = null;
	protected ?array $richObject = null;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $reference) {
		$this->reference = $reference;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getId(): string {
		return $this->reference;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setAccessible(bool $accessible): void {
		$this->accessible = $accessible;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getAccessible(): bool {
		return $this->accessible;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setTitle(string $title): void {
		$this->title = $title;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getTitle(): string {
		return $this->title ?? $this->reference;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setDescription(?string $description): void {
		$this->description = $description;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getDescription(): ?string {
		return $this->description;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setImageUrl(?string $imageUrl): void {
		$this->imageUrl = $imageUrl;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getImageUrl(): ?string {
		return $this->imageUrl;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setImageContentType(?string $contentType): void {
		$this->contentType = $contentType;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getImageContentType(): ?string {
		return $this->contentType;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setUrl(?string $url): void {
		$this->url = $url;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getUrl(): string {
		return $this->url ?? $this->reference;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function setRichObject(string $type, ?array $richObject): void {
		$this->richObjectType = $type;
		$this->richObject = $richObject;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function getRichObjectType(): string {
		if ($this->richObjectType === null) {
			return 'open-graph';
		}
		return $this->richObjectType;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 * @return array<string, mixed>
	 */
	public function getRichObject(): array {
		if ($this->richObject === null) {
			return $this->getOpenGraphObject();
		}
		return $this->richObject;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 * @return OpenGraphObject
	 */
	public function getOpenGraphObject(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getTitle(),
			'description' => $this->getDescription(),
			'thumb' => $this->getImageUrl(),
			'link' => $this->getUrl()
		];
	}

	/**
	 * @param IReference $reference
	 * @return array
	 * @since 25.0.0
	 */
	public static function toCache(IReference $reference): array {
		return [
			'id' => $reference->getId(),
			'title' => $reference->getTitle(),
			'imageUrl' => $reference->getImageUrl(),
			'imageContentType' => $reference->getImageContentType(),
			'description' => $reference->getDescription(),
			'link' => $reference->getUrl(),
			'accessible' => $reference->getAccessible(),
			'richObjectType' => $reference->getRichObjectType(),
			'richObject' => $reference->getRichObject(),
		];
	}

	/**
	 * @param array $cache
	 * @return IReference
	 * @since 25.0.0
	 */
	public static function fromCache(array $cache): IReference {
		$reference = new Reference($cache['id']);
		$reference->setTitle($cache['title']);
		$reference->setDescription($cache['description']);
		$reference->setImageUrl($cache['imageUrl']);
		$reference->setImageContentType($cache['imageContentType']);
		$reference->setUrl($cache['link']);
		$reference->setRichObject($cache['richObjectType'], $cache['richObject']);
		$reference->setAccessible($cache['accessible']);
		return $reference;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 * @return array{richObjectType: string, richObject: array<string, mixed>, openGraphObject: OpenGraphObject, accessible: bool}
	 */
	public function jsonSerialize(): array {
		return [
			'richObjectType' => $this->getRichObjectType(),
			'richObject' => $this->getRichObject(),
			'openGraphObject' => $this->getOpenGraphObject(),
			'accessible' => $this->accessible
		];
	}
}
