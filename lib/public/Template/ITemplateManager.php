<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Template;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * Provides template lookup and convenience helpers for rendering pages.
 *
 * @warning Callers are expected to handle HTTP status selection. Only error
 * related helpers manage status selection and execution termination.
 *
 * @since 32.0.0
 */
interface ITemplateManager {
	/**
	 * Create a template instance for the given app/template pair.
	 *
	 * The returned template uses the given rendering mode and will include a
	 * CSRF token when accessed by default.
	 *
	 * @param string $app App identifier that owns the template
	 * @param string $name Template name without extension
	 * @param TemplateResponse::RENDER_AS_* $renderAs Template rendering mode
	 * @param bool $registerCall Whether a CSRF request token should be included
	 *
	 * @throws TemplateNotFoundException if the template cannot be found
	 *
	 * @since 32.0.0
	 */
	public function getTemplate(string $app, string $name, string $renderAs = TemplateResponse::RENDER_AS_BLANK, bool $registerCall = true): ITemplate;

	/**
	 * Render and print a simple guest page.
	 *
	 * Assigns the provided parameters to the template before rendering.
	 *
	 * @param string $application App identifier that owns the template
	 * @param string $name Template name without extension
	 * @param array $parameters Variables assigned to the template
	 *
	 * @since 32.0.0
	 */
	public function printGuestPage(string $application, string $name, array $parameters = []): void;

	/**
	 * Render and print a fatal error page, then terminate execution.
	 *
	 * The implementation first tries a themed HTML response, then falls back to
	 * an unthemed HTML template, and finally to a plain-text error response.
	 *
	 * @param string $error_msg Error message to show
	 * @param string $hint Optional hint shown below the message (needs to be escaped)
	 * @param int $statusCode HTTP status code to send
	 *
	 * @since 32.0.0
	 */
	public function printErrorPage(string $error_msg, string $hint = '', int $statusCode = 500): never;

	/**
	 * Render and print an exception error page, then terminate execution.
	 *
	 * The exception details are shown in the HTML template, with additional debug
	 * information when debug mode is enabled. Falls back to a plain-text error
	 * page if rendering fails.
	 *
	 * @param \Throwable $exception The exception to render
	 * @param int $statusCode HTTP status code to send
	 *
	 * @since 32.0.0
	 */
	public function printExceptionErrorPage(\Throwable $exception, int $statusCode = 503): never;
}
