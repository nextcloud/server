<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Http\WellKnown;

use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\GenericResponse;
use Test\TestCase;

class GenericResponseTest extends TestCase {
	public function testToHttpResponse(): void {
		$httpResponse = $this->createMock(JSONResponse::class);

		$response = new GenericResponse($httpResponse);

		self::assertSame($httpResponse, $response->toHttpResponse());
	}
}
