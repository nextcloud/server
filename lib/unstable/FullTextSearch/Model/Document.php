<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch\Model;

class Document {
//	private string $id = '';
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



//	public function setId(string $documentId): static {
//		$this->id = $documentId;
//		return $this;
//	}
//
//	public function getId(): string {
//		return $this->id;
//	}

	final public function setFlags(int $flags): static {
		$this->flags = $flags;
		return $this;
	}

	final public function getFlags(): int {
		return $this->flags;
	}

	final public function setAccess(?DocumentAccess $access): static {
		$this->access = $access;
		return $this;
	}

	final public function getAccess(): ?DocumentAccess {
		return $this->access;
	}

	final public function setTitle(string $title): static {
		$this->title = $title;
		return $this;
	}

	final public function getTitle(): string {
		return $this->title;
	}

	final public function setContent(string $content, bool $encoded = false): static {
		$this->content = $content;
		$this->contentSize = strlen($content);
		$this->contentEncoded = $encoded;
		return $this;
	}

	final public function getContent(): string {
		return $this->content;
	}

	final public function isContentEncoded(): bool {
		return $this->contentEncoded;
	}

	final public function getContentSize(): int {
		return $this->contentSize;
	}

	final public function setLastModificationTime(int $lastModificationTime): static {
		$this->lastModificationTime = $lastModificationTime;
		return $this;
	}

	final public function getLastModificationTime(): int {
		return $this->lastModificationTime;
	}

	final public function setTags(array $tags): static {
		$this->tags = $tags;
		return $this;
	}

	final public function getTags(): array {
		return $this->tags;
	}

	final public function setDocumentTags(array $documentTags): static {
		$this->documentTags = $documentTags;
		return $this;
	}

	final public function getDocumentTags(): array {
		return $this->documentTags;
	}

	final public function addPart(string $key, string $part): static {
		$this->parts[$key] = $part;
		return $this;
	}

	final public function setParts(array $parts): static {
		$this->parts = $parts;
		return $this;
	}

	final public function getParts(): array {
		return $this->parts;
	}
}
