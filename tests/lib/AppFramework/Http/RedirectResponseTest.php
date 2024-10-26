<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;

class RedirectResponseTest extends \Test\TestCase {
	/**
	 * @var RedirectResponse
	 */
	protected $response;

	protected function setUp(): void {
		parent::setUp();
		$this->response = new RedirectResponse('/url');
	}


	public function testHeaders(): void {
		$headers = $this->response->getHeaders();
		$this->assertEquals('/url', $headers['Location']);
		$this->assertEquals(Http::STATUS_SEE_OTHER,
			$this->response->getStatus());
	}


	public function testGetRedirectUrl(): void {
		$this->assertEquals('/url', $this->response->getRedirectUrl());
	}
}
