<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use OCP\Files\File;

/**
 * @since 18.0.0
 */
abstract class ACreateEmpty {
	/**
	 * Unique id for the creator to filter templates
	 *
	 * e.g. document/spreadsheet/presentation
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getId(): string;

	/**
	 * Descriptive name for the create action
	 *
	 * e.g Create a new document
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getName(): string;

	/**
	 * Default file extension for the new file
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getExtension(): string;

	/**
	 * Mimetype of the resulting created file
	 *
	 * @since 18.0.0
	 * @return string
	 */
	abstract public function getMimetype(): string;

	/**
	 * Add content when creating empty files
	 *
	 * @since 18.0.0
	 * @param File $file
	 */
	public function create(File $file, ?string $creatorId = null, ?string $templateId = null): void {
	}
}
