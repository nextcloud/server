<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OCP\Server;
use OCP\Template\ITemplateManager;

/**
 * This class provides the templates for ownCloud.
 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
 */
class OC_Template extends \OC\Template\Template {
	/**
	 * Shortcut to print a simple page for guests
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array $parameters Parameters for the template
	 * @return bool
	 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
	 */
	public static function printGuestPage($application, $name, $parameters = []) {
		Server::get(ITemplateManager::class)->printGuestPage($application, $name, $parameters);
		return true;
	}

	/**
	 * Print a fatal error page and terminates the script
	 * @param string $error_msg The error message to show
	 * @param string $hint An optional hint message - needs to be properly escape
	 * @param int $statusCode
	 * @return never
	 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
	 */
	public static function printErrorPage($error_msg, $hint = '', $statusCode = 500) {
		Server::get(ITemplateManager::class)->printErrorPage($error_msg, $hint, $statusCode);
	}

	/**
	 * print error page using Exception details
	 * @param Exception|Throwable $exception
	 * @param int $statusCode
	 * @return never
	 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
	 */
	public static function printExceptionErrorPage($exception, $statusCode = 503) {
		Server::get(ITemplateManager::class)->printExceptionErrorPage($exception, $statusCode);
	}
}
