<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share;

use OCP\AppFramework\Http\TemplateResponse;

/**
 * @since 26.0.0
 */
interface IPublicShareTemplateProvider {
	/**
	 * Returns whether the provider can respond for the given share.
	 * @since 26.0.0
	 */
	public function shouldRespond(IShare $share): bool;
	/**
	 * Returns the a template for a given share.
	 * @since 26.0.0
	 */
	public function renderPage(IShare $share, string $token, string $path): TemplateResponse;
}
