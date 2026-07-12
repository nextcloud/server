<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch\Model;

use JsonException;
use Psr\Log\LoggerInterface;

final class Document {
	private string $id = '';
	private int $flags = 0;
	private ?DocumentAccess $access = null;
	private string $title = '';
	private string $content = '';
	private bool $contentEncoded = false;
	private int $contentSize = 0;
	private int $lastModificationTime = 0;
	private array $tags = [];
	private array $documentTags = [];
	private array $parts = [];
	private string $checksum = '';

	public function setId(string $id): void {
		$this->id = $id;
	}

	public function getId(): string {
		return $this->id;
	}

	public function setFlags(int $flags): Document {
		$this->flags = $flags;
		$this->checksum = '';
		return $this;
	}

	public function getFlags(): int {
		return $this->flags;
	}

	public function setAccess(?DocumentAccess $access): Document {
		$this->access = $access;
		$this->checksum = '';
		return $this;
	}

	public function getAccess(): ?DocumentAccess {
		return $this->access;
	}

	public function setTitle(string $title): Document {
		$this->title = $title;
		$this->checksum = '';
		return $this;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function setContent(string $content, bool $encoded = false): Document {
		$this->content = $content;
		$this->contentSize = strlen($content);
		$this->contentEncoded = $encoded;
		$this->checksum = '';
		return $this;
	}

	public function getContent(): string {
		return $this->content;
	}

	public function isContentEncoded(): bool {
		return $this->contentEncoded;
	}

	public function getContentSize(): int {
		return $this->contentSize;
	}

	public function setLastModificationTime(int $lastModificationTime): Document {
		$this->lastModificationTime = $lastModificationTime;
		return $this;
	}

	public function getLastModificationTime(): int {
		return $this->lastModificationTime;
	}

	public function setTags(array $tags): Document {
		$this->tags = $tags;
		$this->checksum = '';
		return $this;
	}

	public function getTags(): array {
		return $this->tags;
	}

	public function setDocumentTags(array $documentTags): Document {
		$this->documentTags = $documentTags;
		$this->checksum = '';
		return $this;
	}

	public function getDocumentTags(): array {
		return $this->documentTags;
	}

	public function addPart(string $key, string $part): Document {
		$this->parts[$key] = $part;
		$this->checksum = '';
		return $this;
	}

	public function setParts(array $parts): Document {
		$this->parts = $parts;
		$this->checksum = '';
		return $this;
	}

	public function getParts(): array {
		return $this->parts;
	}

	public function getChecksum(): string {
		if ($this->checksum === '') {
			$data = [
				'title' => $this->getTitle(),
				'access' => $this->getAccess(),
				'flags' => $this->getFlags(),
				'tags' => $this->getTags(),
				'documentTags' => $this->getDocumentTags(),
				'parts' => $this->getParts(),
				'content' => $this->getContent()
			];

			try {
				$this->checksum = hash('xxh3', json_encode($data, JSON_THROW_ON_ERROR));
			} catch (JsonException $e) {
				\OCP\Server::get(LoggerInterface::class)->warning('issue while generating checksum', ['exception' => $e]);
			}
		}

		return $this->checksum;
	}

	public function jsonSerialize(): array {
		return [
			'title' => $this->getTitle(),
			'access' => $this->getAccess(),
			'flags' => $this->getFlags(),
			'tags' => $this->getTags(),
			'documentTags' => $this->getDocumentTags(),
			'lastModificationTime' => $this->getLastModificationTime(),
			'contentSize' => $this->getContentSize(),
			'contentEncoded' => $this->isContentEncoded(),
			'checksum' => $this->getChecksum(),
		];
	}
}
