<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\OCS;

use OC\AppFramework\OCS\V2Response;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

class V2ResponseTest extends \Test\TestCase {
	/**
	 * @dataProvider providesStatusCodes
	 */
	public function testStatusCodeMapper(int $expected, int $sc): void {
		$response = new V2Response(new DataResponse([], $sc));
		$this->assertEquals($expected, $response->getStatus());
	}

	public function providesStatusCodes(): array {
		return [
			[Http::STATUS_OK, 200],
			[Http::STATUS_BAD_REQUEST, 104],
			[Http::STATUS_BAD_REQUEST, 1000],
			[201, 201],
			[Http::STATUS_UNAUTHORIZED, OCSController::RESPOND_UNAUTHORISED],
			[Http::STATUS_INTERNAL_SERVER_ERROR, OCSController::RESPOND_SERVER_ERROR],
			[Http::STATUS_NOT_FOUND, OCSController::RESPOND_NOT_FOUND],
			[Http::STATUS_INTERNAL_SERVER_ERROR, OCSController::RESPOND_UNKNOWN_ERROR],
		];
	}
}
