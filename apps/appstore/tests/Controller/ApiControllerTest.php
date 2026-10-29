<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Tests\Controller;

use OC\App\AppStore\Fetcher\CategoryFetcher;
use OCA\Appstore\Controller\ApiController;
use OCP\AppFramework\Http\DataResponse;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class ApiControllerTest extends TestCase {

	private ApiController $apiController;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->apiController = $this->createInstanceWithMocks(ApiController::class);
	}

	public function testListCategories(): void {
		$json = file_get_contents(__DIR__ . '/../fixtures/categories.json');
		$this->mocks[CategoryFetcher::class]
			->expects($this->once())
			->method('get')
			->willReturn(json_decode($json, true)['data']);

		$response = $this->apiController->listCategories();
		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(200, $response->getStatus());

		$jsonResponse = json_encode($response->getData());
		$this->assertJsonStringEqualsJsonFile(__DIR__ . '/../fixtures/categories-api-response.json', $jsonResponse);
	}

	public static function dataCreateProxyPreviewUrl(): array {
		return [
			'empty' => ['', ''],
			'already proxied by the app store' => [
				'https://usercontent.apps.nextcloud.com/aHR0cHM6Ly9leGFtcGxlLm9yZy9hLnBuZw==',
				'https://usercontent.apps.nextcloud.com/aHR0cHM6Ly9leGFtcGxlLm9yZy9hLnBuZw==',
			],
			'source URL' => [
				'https://example.org/a.png',
				'https://usercontent.apps.nextcloud.com/aHR0cHM6Ly9leGFtcGxlLm9yZy9hLnBuZw==',
			],
			'source URL encoding to + and /' => [
				'https://example.org/?a=>>>',
				'https://usercontent.apps.nextcloud.com/aHR0cHM6Ly9leGFtcGxlLm9yZy8_YT0-Pj4=',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataCreateProxyPreviewUrl')]
	public function testCreateProxyPreviewUrl(string $url, string $expected): void {
		$this->assertSame($expected, self::invokePrivate($this->apiController, 'createProxyPreviewUrl', [$url]));
	}
}
