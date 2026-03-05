<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Template;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * @since 32.0.0
 */
interface ITemplateManager {
	/**
	 * @param TemplateResponse::RENDER_AS_* $renderAs
	 * @throws TemplateNotFoundException if the template cannot be found
	 * @since 32.0.0
	 */
	public function getTemplate(string $app, string $name, string $renderAs = TemplateResponse::RENDER_AS_BLANK, bool $registerCall = true): ITemplate;

	/**
	 * Shortcut to print a simple page for guests
	 * @since 32.0.0
	 */
	public function printGuestPage(string $application, string $name, array $parameters = []): void;

	/**
	 * Print a fatal error page and terminates the script
	 * @since 32.0.0
	 * @param string $error_msg The error message to show
	 * @param string $hint An optional hint message - needs to be properly escape
	 */
	public function printErrorPage(string $error_msg, string $hint = '', int $statusCode = 500): never;

	/**
	 * Print error page using Exception details
	 * @since 32.0.0
	 */
	public function printExceptionErrorPage(\Throwable $exception, int $statusCode = 503): never;
}
