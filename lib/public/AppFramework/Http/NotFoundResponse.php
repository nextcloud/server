<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A generic 404 response showing an 404 error page as well to the end-user
 * @since 8.1.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends TemplateResponse<Http::STATUS_*, array<string, mixed>>
 */
class NotFoundResponse extends TemplateResponse {
	/**
	 * @param S $status
	 * @param H $headers
	 * @since 8.1.0
	 */
	public function __construct(int $status = Http::STATUS_NOT_FOUND, array $headers = []) {
		parent::__construct('core', '404', [], 'guest', $status, $headers);

		$this->setContentSecurityPolicy(new ContentSecurityPolicy());
	}
}
