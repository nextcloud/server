<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Redirects to a different URL
 * @since 7.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class RedirectResponse extends Response {
	private $redirectURL;

	/**
	 * Creates a response that redirects to a url
	 * @param string $redirectURL the url to redirect to
	 * @param S $status
	 * @param H $headers
	 * @since 7.0.0
	 */
	public function __construct(string $redirectURL, int $status = Http::STATUS_SEE_OTHER, array $headers = []) {
		parent::__construct($status, $headers);

		$this->redirectURL = $redirectURL;
		$this->addHeader('Location', $redirectURL);
	}


	/**
	 * @return string the url to redirect
	 * @since 7.0.0
	 */
	public function getRedirectURL() {
		return $this->redirectURL;
	}
}
