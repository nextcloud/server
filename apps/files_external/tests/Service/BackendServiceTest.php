<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Tests\Service;

use \OCA\Files_External\Service\BackendService;

class BackendServiceTest extends \Test\TestCase {

	/** @var \OCP\IConfig */
	protected $config;

	/** @var \OCP\IL10N */
	protected $l10n;

	protected function setUp() {
		$this->config = $this->getMock('\OCP\IConfig');
		$this->l10n = $this->getMock('\OCP\IL10N');
	}

	/**
	 * @param string $class
	 *
	 * @return \OCA\Files_External\Lib\Backend\Backend
	 */
	protected function getBackendMock($class) {
		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
			->disableOriginalConstructor()
			->getMock();
		$backend->method('getIdentifier')->will($this->returnValue('identifier:'.$class));
		$backend->method('getIdentifierAliases')->will($this->returnValue(['identifier:'.$class]));
		return $backend;
	}

	/**
	 * @param string $class
	 *
	 * @return \OCA\Files_External\Lib\Auth\AuthMechanism
	 */
	protected function getAuthMechanismMock($class) {
		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Auth\AuthMechanism')
			->disableOriginalConstructor()
			->getMock();
		$backend->method('getIdentifier')->will($this->returnValue('identifier:'.$class));
		$backend->method('getIdentifierAliases')->will($this->returnValue(['identifier:'.$class]));
		return $backend;
	}

	public function testRegisterBackend() {
		$service = new BackendService($this->config, $this->l10n);

		$backend = $this->getBackendMock('\Foo\Bar');

		$backendAlias = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
			->disableOriginalConstructor()
			->getMock();
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

	public function testBackendProvider() {
		$service = new BackendService($this->config, $this->l10n);

		$backend1 = $this->getBackendMock('\Foo\Bar');
		$backend2 = $this->getBackendMock('\Bar\Foo');

		$providerMock = $this->getMock('\OCA\Files_External\Lib\Config\IBackendProvider');
		$providerMock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend1, $backend2]);
		$service->registerBackendProvider($providerMock);

		$this->assertEquals($backend1, $service->getBackend('identifier:\Foo\Bar'));
		$this->assertEquals($backend2, $service->getBackend('identifier:\Bar\Foo'));

		$this->assertCount(2, $service->getBackends());
	}

	public function testAuthMechanismProvider() {
		$service = new BackendService($this->config, $this->l10n);

		$backend1 = $this->getAuthMechanismMock('\Foo\Bar');
		$backend2 = $this->getAuthMechanismMock('\Bar\Foo');

		$providerMock = $this->getMock('\OCA\Files_External\Lib\Config\IAuthMechanismProvider');
		$providerMock->expects($this->once())
			->method('getAuthMechanisms')
			->willReturn([$backend1, $backend2]);
		$service->registerAuthMechanismProvider($providerMock);

		$this->assertEquals($backend1, $service->getAuthMechanism('identifier:\Foo\Bar'));
		$this->assertEquals($backend2, $service->getAuthMechanism('identifier:\Bar\Foo'));

		$this->assertCount(2, $service->getAuthMechanisms());
	}

	public function testMultipleBackendProviders() {
		$service = new BackendService($this->config, $this->l10n);

		$backend1a = $this->getBackendMock('\Foo\Bar');
		$backend1b = $this->getBackendMock('\Bar\Foo');

		$backend2 = $this->getBackendMock('\Dead\Beef');

		$provider1Mock = $this->getMock('\OCA\Files_External\Lib\Config\IBackendProvider');
		$provider1Mock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend1a, $backend1b]);
		$service->registerBackendProvider($provider1Mock);
		$provider2Mock = $this->getMock('\OCA\Files_External\Lib\Config\IBackendProvider');
		$provider2Mock->expects($this->once())
			->method('getBackends')
			->willReturn([$backend2]);
		$service->registerBackendProvider($provider2Mock);

		$this->assertEquals($backend1a, $service->getBackend('identifier:\Foo\Bar'));
		$this->assertEquals($backend1b, $service->getBackend('identifier:\Bar\Foo'));
		$this->assertEquals($backend2, $service->getBackend('identifier:\Dead\Beef'));

		$this->assertCount(3, $service->getBackends());
	}

	public function testUserMountingBackends() {
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->will($this->returnValueMap([
				['files_external', 'allow_user_mounting', 'yes', 'yes'],
				['files_external', 'user_mounting_backends', '', 'identifier:\User\Mount\Allowed,identifier_alias']
			]));

		$service = new BackendService($this->config, $this->l10n);

		$backendAllowed = $this->getBackendMock('\User\Mount\Allowed');
		$backendAllowed->expects($this->never())
			->method('removeVisibility');
		$backendNotAllowed = $this->getBackendMock('\User\Mount\NotAllowed');
		$backendNotAllowed->expects($this->once())
			->method('removeVisibility')
			->with(BackendService::VISIBILITY_PERSONAL);

		$backendAlias = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
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

	public function testGetAvailableBackends() {
		$service = new BackendService($this->config, $this->l10n);

		$backendAvailable = $this->getBackendMock('\Backend\Available');
		$backendAvailable->expects($this->once())
			->method('checkDependencies')
			->will($this->returnValue([]));
		$backendNotAvailable = $this->getBackendMock('\Backend\NotAvailable');
		$backendNotAvailable->expects($this->once())
			->method('checkDependencies')
			->will($this->returnValue([
				$this->getMockBuilder('\OCA\Files_External\Lib\MissingDependency')
					->disableOriginalConstructor()
					->getMock()
			]));

		$service->registerBackend($backendAvailable);
		$service->registerBackend($backendNotAvailable);

		$availableBackends = $service->getAvailableBackends();
		$this->assertArrayHasKey('identifier:\Backend\Available', $availableBackends);
		$this->assertArrayNotHasKey('identifier:\Backend\NotAvailable', $availableBackends);
	}

}

