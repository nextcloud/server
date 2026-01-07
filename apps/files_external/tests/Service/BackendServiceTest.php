<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Service;

use OCA\Files_External\Config\IConfigHandler;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Service\BackendService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;

class BackendServiceTest extends \Test\TestCase {
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
	}

	/**
	 * @return \OCA\Files_External\Lib\Backend\Backend&MockObject
	 */
	protected function getBackendMock(string $class) {
		$backend = $this->createMock(Backend::class);
		$backend->method('getIdentifier')->willReturn('identifier:' . $class);
		$backend->method('getIdentifierAliases')->willReturn(['identifier:' . $class]);
		return $backend;
	}

	/**
	 * @param string $class
	 *
	 * @return AuthMechanism&MockObject
	 */
	protected function getAuthMechanismMock($class) {
		$backend = $this->createMock(AuthMechanism::class);
		$backend->method('getIdentifier')->willReturn('identifier:' . $class);
		$backend->method('getIdentifierAliases')->willReturn(['identifier:' . $class]);
		return $backend;
	}

	public function testRegisterBackend(): void {
		$service = new BackendService($this->appConfig);

		$backend = $this->getBackendMock('\Foo\Bar');

		/** @var \OCA\Files_External\Lib\Backend\Backend&MockObject $backendAlias */
		$backendAlias = $this->createMock(Backend::class);
		$backendAlias->method('getIdentifierAliases')
			->willReturn(['identifier_real', 'identifier_alias']);
		$backendAlias->method('getIdentifier')
			->willReturn('identifier_real');

		$service->registerBackend($backend);
		$service->registerBackend($backendAlias);

		$this->assertEquals($backend, $service->getBackend('identifier:\Foo\Bar'));
		$this->assertEquals($backendAlias, $service->getBackend('identifier_real'));
		$this->assertEquals($backendAlias, $service->getBackend('identifier_alias'));

		$backends = $service->getBackends();
		$this->assertCount(2, $backends);
		$this->assertArrayHasKey('identifier:\Foo\Bar', $backends);
		$this->assertArrayHasKey('identifier_real', $backends);
		$this->assertArrayNotHasKey('identifier_alias', $backends);
	}

	public function testBackendProvider(): void {
		$service = new BackendService($this->appConfig);

		$backend1 = $this->getBackendMock('\Foo\Bar');
		$backend2 = $this->getBackendMock('\Bar\Foo');

		/** @var IBackendProvider&MockObject $providerMock */
		$providerMock = $this->createMock(IBackendProvider::class);
		$providerMock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend1, $backend2]);
		$service->registerBackendProvider($providerMock);

		$this->assertEquals($backend1, $service->getBackend('identifier:\Foo\Bar'));
		$this->assertEquals($backend2, $service->getBackend('identifier:\Bar\Foo'));

		$this->assertCount(2, $service->getBackends());
	}

	public function testAuthMechanismProvider(): void {
		$service = new BackendService($this->appConfig);

		$backend1 = $this->getAuthMechanismMock('\Foo\Bar');
		$backend2 = $this->getAuthMechanismMock('\Bar\Foo');

		/** @var IAuthMechanismProvider&MockObject $providerMock */
		$providerMock = $this->createMock(IAuthMechanismProvider::class);
		$providerMock->expects($this->once())
			->method('getAuthMechanisms')
			->willReturn([$backend1, $backend2]);
		$service->registerAuthMechanismProvider($providerMock);

		$this->assertEquals($backend1, $service->getAuthMechanism('identifier:\Foo\Bar'));
		$this->assertEquals($backend2, $service->getAuthMechanism('identifier:\Bar\Foo'));

		$this->assertCount(2, $service->getAuthMechanisms());
	}

	public function testMultipleBackendProviders(): void {
		$service = new BackendService($this->appConfig);

		$backend1a = $this->getBackendMock('\Foo\Bar');
		$backend1b = $this->getBackendMock('\Bar\Foo');

		$backend2 = $this->getBackendMock('\Dead\Beef');

		/** @var IBackendProvider&MockObject $provider1Mock */
		$provider1Mock = $this->createMock(IBackendProvider::class);
		$provider1Mock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend1a, $backend1b]);
		$service->registerBackendProvider($provider1Mock);
		/** @var IBackendProvider&MockObject $provider2Mock */
		$provider2Mock = $this->createMock(IBackendProvider::class);
		$provider2Mock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend2]);
		$service->registerBackendProvider($provider2Mock);

		$this->assertEquals($backend1a, $service->getBackend('identifier:\Foo\Bar'));
		$this->assertEquals($backend1b, $service->getBackend('identifier:\Bar\Foo'));
		$this->assertEquals($backend2, $service->getBackend('identifier:\Dead\Beef'));

		$this->assertCount(3, $service->getBackends());
	}

	public function testUserMountingBackends(): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('files_external', 'user_mounting_backends')
			->willReturn('identifier:\User\Mount\Allowed,identifier_alias');
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('files_external', 'allow_user_mounting')
			->willReturn(true);

		$service = new BackendService($this->appConfig);

		$backendAllowed = $this->getBackendMock('\User\Mount\Allowed');
		$backendAllowed->expects($this->never())
			->method('removeVisibility');
		$backendNotAllowed = $this->getBackendMock('\User\Mount\NotAllowed');
		$backendNotAllowed->expects($this->once())
			->method('removeVisibility')
			->with(BackendService::VISIBILITY_PERSONAL);

		$backendAlias = $this->getMockBuilder(Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$backendAlias->method('getIdentifierAliases')
			->willReturn(['identifier_real', 'identifier_alias']);
		$backendAlias->expects($this->never())
			->method('removeVisibility');

		$service->registerBackend($backendAllowed);
		$service->registerBackend($backendNotAllowed);
		$service->registerBackend($backendAlias);
	}

	public function testGetAvailableBackends(): void {
		$service = new BackendService($this->appConfig);

		$backendAvailable = $this->getBackendMock('\Backend\Available');
		$backendAvailable->expects($this->once())
			->method('checkDependencies')
			->willReturn([]);
		$backendNotAvailable = $this->getBackendMock('\Backend\NotAvailable');
		$backendNotAvailable->expects($this->once())
			->method('checkDependencies')
			->willReturn([
				$this->getMockBuilder('\OCA\Files_External\Lib\MissingDependency')
					->disableOriginalConstructor()
					->getMock()
			]);

		$service->registerBackend($backendAvailable);
		$service->registerBackend($backendNotAvailable);

		$availableBackends = $service->getAvailableBackends();
		$this->assertArrayHasKey('identifier:\Backend\Available', $availableBackends);
		$this->assertArrayNotHasKey('identifier:\Backend\NotAvailable', $availableBackends);
	}

	public static function invalidConfigPlaceholderProvider(): array {
		return [
			[['@user']],
			[['$user']],
			[['hællo']],
			[['spa ce']],
			[['yo\o']],
			[['<script>…</script>']],
			[['xxyoloxx', 'invÆlid']],
			[['tautology', 'tautology']],
			[['tautology2', 'TAUTOLOGY2']],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'invalidConfigPlaceholderProvider')]
	public function testRegisterConfigHandlerInvalid(array $placeholders): void {
		$this->expectException(\RuntimeException::class);

		$service = new BackendService($this->appConfig);
		$mock = $this->createMock(IConfigHandler::class);
		$cb = function () use ($mock) {
			return $mock;
		};
		foreach ($placeholders as $placeholder) {
			$service->registerConfigHandler($placeholder, $cb);
		}
	}

	public function testConfigHandlers(): void {
		$service = new BackendService($this->appConfig);
		$mock = $this->createMock(IConfigHandler::class);
		$mock->expects($this->exactly(3))
			->method('handle');
		$cb = function () use ($mock) {
			return $mock;
		};
		$service->registerConfigHandler('one', $cb);
		$service->registerConfigHandler('2', $cb);
		$service->registerConfigHandler('Three', $cb);

		/** @var IConfigHandler[] $handlers */
		$handlers = $service->getConfigHandlers();

		foreach ($handlers as $handler) {
			$handler->handle('Something');
		}
	}
}
