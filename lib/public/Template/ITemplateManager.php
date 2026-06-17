<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Template;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * Provides helpers for locating and rendering server-side templates.
 *
 * @since 32.0.0
 */
interface ITemplateManager {
	/**
	 * Create a template for the given app and template name.
	 *
	 * @param string $app App identifier that owns the template
	 * @param string $name Template name without extension
	 * @param TemplateResponse::RENDER_AS_* $renderAs Rendering mode / layout wrapper
	 * @param bool $registerCall Whether to register the request for CSRF token injection
	 * @throws TemplateNotFoundException if the template cannot be found
	 * @since 32.0.0
	 */
	public function getTemplate(string $app, string $name, string $renderAs = TemplateResponse::RENDER_AS_BLANK, bool $registerCall = true): ITemplate;

	/**
	 * Render and print a guest page.
	 *
	 * Assigns the provided parameters to the template before printing it.
	 * This helper does not set an HTTP status code or terminate execution.
	 *
	 * @param string $application App identifier that owns the template
	 * @param string $name Template name without extension
	 * @param array $parameters Template variables to assign
	 * @since 32.0.0
	 */
	public function printGuestPage(string $application, string $name, array $parameters = []): void;

	/**
	 * Render and print an error page, then terminate execution.
	 *
	 * Sets the HTTP status code before rendering. Falls back from the themed
	 * error page to an unthemed template and finally to plain-text output if
	 * rendering fails.
	 *
	 * @param string $error_msg Error message to show
	 * @param string $hint Optional hint shown with the error
	 * @param int $statusCode HTTP status code to send
	 * @since 32.0.0
	 */
	public function printErrorPage(string $error_msg, string $hint = '', int $statusCode = 500): never;

	/**
	 * Render and print an exception error page, then terminate execution.
	 *
	 * Sets the HTTP status code before rendering. Uses the exception to populate
	 * the error view and falls back to plain-text output if rendering fails.
	 *
	 * @param \Throwable $exception Exception to render
	 * @param int $statusCode HTTP status code to send
	 * @since 32.0.0
	 */
	public function printExceptionErrorPage(\Throwable $exception, int $statusCode = 503): never;
}
