<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Template;

use OCP\Files\GenericFileException;

/**
 * @since 21.0.0
 */
interface ITemplateManager {
	/**
	 * Register a template type support
	 *
	 * @param callable(): TemplateFileCreator $callback A callback which returns the TemplateFileCreator instance to register
	 * @since 21.0.0
	 */
	public function registerTemplateFileCreator(callable $callback): void;

	/**
	 * Get a list of available file creators
	 *
	 * @return array
	 * @since 21.0.0
	 */
	public function listCreators(): array;

	/**
	 * Get a list of available file creators and their offered templates
	 *
	 * @return array
	 * @since 21.0.0
	 */
	public function listTemplates(): array;

	/**
	 * @return bool
	 * @since 21.0.0
	 */
	public function hasTemplateDirectory(): bool;

	/**
	 * @param string $path
	 * @return void
	 * @since 21.0.0
	 */
	public function setTemplatePath(string $path): void;

	/**
	 * @return string
	 * @since 21.0.0
	 */
	public function getTemplatePath(): string;

	/**
	 * @param string|null $path
	 * @param string|null $userId
	 * @since 21.0.0
	 */
	public function initializeTemplateDirectory(?string $path = null, ?string $userId = null, $copyTemplates = true): string;

	/**
	 * @param string $filePath
	 * @param string $templateId
	 * @return array
	 * @throws GenericFileException
	 * @since 21.0.0
	 */
	public function createFromTemplate(string $filePath, string $templateId = '', string $templateType = 'user'): array;
}
