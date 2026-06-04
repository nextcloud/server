<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\WellKnown;

use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\WellKnown\JrdResponse;
use Test\TestCase;

class JrdResponseTest extends TestCase {
	public function testEmptyToHttpResponse(): void {
		$response = new JrdResponse('subject');
		$httpResponse = $response->toHttpResponse();

		self::assertTrue($response->isEmpty());
		self::assertInstanceOf(JSONResponse::class, $httpResponse);
		/** @var JSONResponse $httpResponse */
		self::assertEquals(
			[
				'subject' => 'subject',
			],
			$httpResponse->getData()
		);
	}

	public function testComplexToHttpResponse(): void {
		$response = new JrdResponse('subject');
		$response->addAlias('alias');
		$response->addAlias('blias');
		$response->addProperty('propa', 'a');
		$response->addProperty('propb', null);
		$response->setExpires('tomorrow');
		$response->addLink('rel', null, null);
		$response->addLink('rel', 'type', null);
		$response->addLink('rel', 'type', 'href', ['title' => 'titlevalue']);
		$response->addLink('rel', 'type', 'href', ['title' => 'titlevalue'], ['propx' => 'valx']);
		$httpResponse = $response->toHttpResponse();

		self::assertFalse($response->isEmpty());
		self::assertInstanceOf(JSONResponse::class, $httpResponse);
		/** @var JSONResponse $httpResponse */
		self::assertEquals(
			[
				'subject' => 'subject',
				'aliases' => [
					'alias',
					'blias',
				],
				'properties' => [
					'propa' => 'a',
					'propb' => null,
				],
				'expires' => 'tomorrow',
				'links' => [
					[
						'rel' => 'rel',
					],
					[
						'rel' => 'rel',
						'type' => 'type',
					],
					[
						'rel' => 'rel',
						'type' => 'type',
						'href' => 'href',
						'titles' => [
							'title' => 'titlevalue',
						],
					],
					[
						'rel' => 'rel',
						'type' => 'type',
						'href' => 'href',
						'titles' => [
							'title' => 'titlevalue',
						],
						'properties' => [
							'propx' => 'valx',
						],
					],
				]
			],
			$httpResponse->getData()
		);
	}
}
