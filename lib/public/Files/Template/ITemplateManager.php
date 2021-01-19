<?php
/**
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
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
 *
 */

declare(strict_types=1);


namespace OCP\Files\Template;

use OCP\Files\GenericFileException;

/**
 * @since 21.0.0
 */
interface ITemplateManager {

	/**
	 * Register a template type support
	 *
	 * @param TemplateFileCreator $templateType
	 * @since 21.0.0
	 */
	public function registerTemplateFileCreator(TemplateFileCreator $templateType): void;

	/**
	 * Register a custom template provider class that is able to inject custom templates
	 * in addition to the user defined ones
	 *
	 * @param string $providerClass
	 * @since 21.0.0
	 */
	public function registerTemplateProvider(string $providerClass): void;

	/**
	 * Get a list of available file creators and their offered templates
	 *
	 * @return array
	 * @since 21.0.0
	 */
	public function listCreators(): array;

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
	public function initializeTemplateDirectory(string $path = null, string $userId = null): void;

	/**
	 * @param string $filePath
	 * @param string $templateId
	 * @return array
	 * @throws GenericFileException
	 * @since 21.0.0
	 */
	public function createFromTemplate(string $filePath, string $templateId = '', string $templateType = 'user'): array;
}
