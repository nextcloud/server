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
interface ICustomTemplateProvider {
	/**
	 * Return a list of additional templates that the template provider is offering
	 *
	 * @return Template[]
	 * @since 21.0.0
	 */
	public function getCustomTemplates(string $mimetype): array;

	/**
	 * Return the file for a given template id
	 *
	 * @param string $template identifier of the template
	 * @return File
	 * @since 21.0.0
	 */
	public function getCustomTemplate(string $template): File;
}
