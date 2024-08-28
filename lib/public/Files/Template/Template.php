<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Template;

use OCP\Files\File;

/**
 * @since 21.0.0
 */
final class Template implements \JsonSerializable {
	/** @var string */
	private $templateType;
	/** @var string */
	private $templateId;
	/** @var File */
	private $file;
	/** @var bool */
	private $hasPreview = false;
	/** @var string|null */
	private $previewUrl = null;
	/** @var array */
	private $fields = [];

	/**
	 * @since 21.0.0
	 */
	public function __construct(string $templateType, string $templateId, File $file) {
		$this->templateType = $templateType;
		$this->templateId = $templateId;
		$this->file = $file;
	}

	/**
	 * @since 21.0.0
	 */
	public function setCustomPreviewUrl(string $previewUrl): void {
		$this->previewUrl = $previewUrl;
	}

	/**
	 * @since 21.0.0
	 */
	public function setHasPreview(bool $hasPreview): void {
		$this->hasPreview = $hasPreview;
	}

	/**
	 * @since 30.0.0
	 */
	public function setFields(array $fields): void {
		$this->fields = $fields;
	}

	/**
	 * @since 21.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'templateType' => $this->templateType,
			'templateId' => $this->templateId,
			'basename' => $this->file->getName(),
			'etag' => $this->file->getEtag(),
			'fileid' => $this->file->getId(),
			'filename' => $this->templateId,
			'lastmod' => $this->file->getMTime(),
			'mime' => $this->file->getMimetype(),
			'size' => $this->file->getSize(),
			'type' => $this->file->getType(),
			'hasPreview' => $this->hasPreview,
			'previewUrl' => $this->previewUrl,
			'fields' => $this->fields
		];
	}
}
