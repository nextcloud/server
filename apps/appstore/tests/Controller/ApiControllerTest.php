<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Tests\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OC\Installer;
use OCA\Appstore\Controller\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;
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

	public function testUninstallApp(): void {
		$this->mocks[AppManager::class]->method('cleanAppId')
			->willReturnArgument(0);
		$this->mocks[AppManager::class]->method('isEnabledForAnyone')
			->willReturn(false);
		$this->mocks[Installer::class]->expects($this->once())
			->method('removeApp')
			->with('myapp')
			->willReturn(true);
		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'apps.app.uninstalled'
				&& $event->getLogMessage() === 'App "%s" uninstalled'
				&& $event->getParameters() === ['app' => 'myapp']));

		$this->apiController->uninstallApp('myapp');
	}

	public function testUninstallAppFailed(): void {
		$this->mocks[AppManager::class]->method('cleanAppId')
			->willReturnArgument(0);
		$this->mocks[Installer::class]->method('removeApp')
			->willReturn(false);
		$this->mocks[IEventDispatcher::class]->expects($this->never())
			->method('dispatchTyped');

		$this->expectException(OCSException::class);
		$this->apiController->uninstallApp('myapp');
	}
}
