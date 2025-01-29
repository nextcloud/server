<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\IURLGenerator;

/**
 * Redirects to the default app
 *
 * @since 16.0.0
 * @deprecated 23.0.0 Use RedirectResponse() with IURLGenerator::linkToDefaultPageUrl() instead
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends RedirectResponse<Http::STATUS_*, array<string, mixed>>
 */
class RedirectToDefaultAppResponse extends RedirectResponse {
	/**
	 * Creates a response that redirects to the default app
	 *
	 * @param S $status
	 * @param H $headers
	 * @since 16.0.0
	 * @deprecated 23.0.0 Use RedirectResponse() with IURLGenerator::linkToDefaultPageUrl() instead
	 */
	public function __construct(int $status = Http::STATUS_SEE_OTHER, array $headers = []) {
		/** @var IURLGenerator $urlGenerator */
		$urlGenerator = \OC::$server->get(IURLGenerator::class);
		parent::__construct($urlGenerator->linkToDefaultPageUrl(), $status, $headers);
	}
}
