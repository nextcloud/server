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

namespace OC\Collaboration\Reference;

class Reference implements \OCP\Collaboration\Reference\IReference, \JsonSerializable {
	private string $reference;

	private bool $accessible = true;

	private ?string $title = null;
	private ?string $description = null;
	private ?string $imageUrl = null;
	private ?string $url = null;

	private ?string $richObjectType = null;
	private ?array $richObject = null;

	public function __construct(string $reference) {
		$this->reference = $reference;
	}

	public function getId(): string {
		return $this->reference;
	}

	public function setAccessible(bool $accessible): void {
		$this->accessible = $accessible;
	}

	public function setTitle(string $title): void {
		$this->title = $title;
	}

	public function getTitle(): string {
		return $this->title ?? $this->reference;
	}

	public function setDescription(?string $description): void {
		$this->description = $description;
	}

	public function getDescription(): ?string {
		return $this->description;
	}

	public function setImageUrl(?string $imageUrl): void {
		$this->imageUrl = $imageUrl;
	}

	public function getImageUrl(): ?string {
		return $this->imageUrl;
	}

	public function setUrl(?string $url): void {
		$this->url = $url;
	}

	public function getUrl(): ?string {
		return $this->url;
	}

	public function setRichObject(string $type, array $richObject): void {
		$this->richObjectType = $type;
		$this->richObject = $richObject;
	}

	public function getRichObjectType(): string {
		if (!$this->richObjectType) {
			return 'open-graph';
		}
		return $this->richObjectType;
	}

	public function getRichObject(): array {
		if (!$this->richObject) {
			return $this->getOpenGraphObject();
		}
		return $this->richObject;
	}

	public function getOpenGraphObject(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getTitle(),
			'description' => $this->getDescription(),
			'thumb' => $this->getImageUrl(),
			'link' => $this->getUrl()
		];
	}

	public static function toCache(Reference $reference): array {
		return [
			'id' => $reference->getId(),
			'title' => $reference->getTitle(),
			'imageUrl' => $reference->getImageUrl(),
			'description' => $reference->getDescription(),
			'link' => $reference->getUrl(),
			'accessible' => $reference->accessible,
			'richObjectType' => $reference->getRichObjectType(),
			'richObject' => $reference->getRichObject(),
		];
	}

	public static function fromCache(array $cache): Reference {
		$reference = new Reference($cache['id']);
		$reference->setTitle($cache['title']);
		$reference->setDescription($cache['description']);
		$reference->setImageUrl($cache['imageUrl']);
		$reference->setUrl($cache['link']);
		$reference->setRichObject($cache['richObjectType'], $cache['richObject']);
		$reference->setAccessible($cache['accessible']);
		return $reference;
	}

	public function jsonSerialize() {
		return [
			'richObjectType' => $this->getRichObjectType(),
			'richObject' => $this->getRichObject(),
			'openGraphObject' => $this->getOpenGraphObject(),
			'accessible' => $this->accessible
		];
	}
}
