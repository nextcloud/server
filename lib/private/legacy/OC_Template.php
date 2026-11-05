<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Template\Template;
use OCP\Server;
use OCP\Template\ITemplateManager;

/**
 * This class provides the templates for ownCloud.
 * @deprecated 32.0.0 Use \OCP\Template\ITemplateManager instead
 */
class OC_Template extends Template {
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
}
