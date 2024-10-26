<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotPermittedException;

/**
 * This class represents a file that is only hold in memory.
 *
 * @since 16.0.0
 */
class InMemoryFile implements ISimpleFile {
	/**
	 * Holds the file name.
	 */
	private string $name;

	/**
	 * Holds the file contents.
	 */
	private string $contents;

	/**
	 * InMemoryFile constructor.
	 *
	 * @param string $name The file name
	 * @param string $contents The file contents
	 * @since 16.0.0
	 */
	public function __construct(string $name, string $contents) {
		$this->name = $name;
		$this->contents = $contents;
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getSize(): int|float {
		return strlen($this->contents);
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getETag(): string {
		return '';
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getMTime(): int {
		return time();
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getContent(): string {
		return $this->contents;
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function putContent($data): void {
		$this->contents = $data;
	}

	/**
	 * In memory files can't be deleted.
	 *
	 * @since 16.0.0
	 */
	public function delete(): void {
		// unimplemented for in memory files
	}

	/**
	 * @inheritdoc
	 * @since 16.0.0
	 */
	public function getMimeType(): string {
		$fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		return $fileInfo->buffer($this->contents);
	}

	/**
	 * {@inheritDoc}
	 * @since 24.0.0
	 */
	public function getExtension(): string {
		return \pathinfo($this->name, PATHINFO_EXTENSION);
	}

	/**
	 * Stream reading is unsupported for in memory files.
	 *
	 * @throws NotPermittedException
	 * @since 16.0.0
	 */
	public function read() {
		throw new NotPermittedException(
			'Stream reading is unsupported for in memory files'
		);
	}

	/**
	 * Stream writing isn't available for in memory files.
	 *
	 * @throws NotPermittedException
	 * @since 16.0.0
	 */
	public function write() {
		throw new NotPermittedException(
			'Stream writing is unsupported for in memory files'
		);
	}
}
