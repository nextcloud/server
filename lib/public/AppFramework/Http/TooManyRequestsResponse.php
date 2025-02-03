<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\Template;

/**
 * A generic 429 response showing an 404 error page as well to the end-user
 * @since 19.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class TooManyRequestsResponse extends Response {
	/**
	 * @param S $status
	 * @param H $headers
	 * @since 19.0.0
	 */
	public function __construct(int $status = Http::STATUS_TOO_MANY_REQUESTS, array $headers = []) {
		parent::__construct($status, $headers);

		$this->setContentSecurityPolicy(new ContentSecurityPolicy());
	}

	/**
	 * @return string
	 * @since 19.0.0
	 */
	public function render() {
		$template = new Template('core', '429', 'blank');
		return $template->fetchPage();
	}
}
