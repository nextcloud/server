<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OC\Security\IdentityProof\Key;
use OC\Security\IdentityProof\Manager;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\ServerVersion;
use Test\TestCase;

class OCSControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var CapabilitiesManager|\PHPUnit\Framework\MockObject\MockObject */
	private $capabilitiesManager;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
	private $keyManager;
	/** @var ServerVersion|\PHPUnit\Framework\MockObject\MockObject */
	private $serverVersion;
	/** @var OCSController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->capabilitiesManager = $this->createMock(CapabilitiesManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->keyManager = $this->createMock(Manager::class);
		$serverVersion = Server::get(ServerVersion::class);

		$this->controller = new OCSController(
			'core',
			$this->request,
			$this->capabilitiesManager,
			$this->userSession,
			$this->userManager,
			$this->keyManager,
			$serverVersion
		);
	}

	public function testGetConfig() {
		$this->request->method('getServerHost')
			->willReturn('awesomehost.io');

		$data = [
			'version' => '1.7',
			'website' => 'Nextcloud',
			'host' => 'awesomehost.io',
			'contact' => '',
			'ssl' => 'false',
		];

		$expected = new DataResponse($data);
		$this->assertEquals($expected, $this->controller->getConfig());

		return new DataResponse($data);
	}

	public function testGetCapabilities(): void {
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		$serverVersion = Server::get(ServerVersion::class);

		$result = [];
		$result['version'] = [
			'major' => $serverVersion->getMajorVersion(),
			'minor' => $serverVersion->getMinorVersion(),
			'micro' => $serverVersion->getPatchVersion(),
			'string' => $serverVersion->getVersionString(),
			'edition' => '',
			'extendedSupport' => false
		];

		$capabilities = [
			'foo' => 'bar',
			'a' => [
				'b' => true,
				'c' => 11,
			]
		];
		$this->capabilitiesManager->method('getCapabilities')
			->willReturn($capabilities);

		$result['capabilities'] = $capabilities;

		$expected = new DataResponse($result);
		$expected->setETag(md5(json_encode($result)));
		$this->assertEquals($expected, $this->controller->getCapabilities());
	}

	public function testGetCapabilitiesPublic(): void {
		$this->userSession->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$serverVersion = Server::get(ServerVersion::class);

		$result = [];
		$result['version'] = [
			'major' => $serverVersion->getMajorVersion(),
			'minor' => $serverVersion->getMinorVersion(),
			'micro' => $serverVersion->getPatchVersion(),
			'string' => $serverVersion->getVersionString(),
			'edition' => '',
			'extendedSupport' => false
		];

		$capabilities = [
			'foo' => 'bar',
			'a' => [
				'b' => true,
				'c' => 11,
			]
		];
		$this->capabilitiesManager->method('getCapabilities')
			->with(true)
			->willReturn($capabilities);

		$result['capabilities'] = $capabilities;

		$expected = new DataResponse($result);
		$expected->setETag(md5(json_encode($result)));
		$this->assertEquals($expected, $this->controller->getCapabilities());
	}

	public function testPersonCheckValid(): void {
		$this->userManager->method('checkPassword')
			->with(
				$this->equalTo('user'),
				$this->equalTo('pass')
			)->willReturn($this->createMock(IUser::class));

		$expected = new DataResponse([
			'person' => [
				'personid' => 'user'
			]
		]);
		$this->assertEquals($expected, $this->controller->personCheck('user', 'pass'));
	}

	public function testPersonInvalid(): void {
		$this->userManager->method('checkPassword')
			->with(
				$this->equalTo('user'),
				$this->equalTo('wrongpass')
			)->willReturn(false);

		$expected = new DataResponse([], 102);
		$expected->throttle();
		$this->assertEquals($expected, $this->controller->personCheck('user', 'wrongpass'));
	}

	public function testPersonNoLogin(): void {
		$this->userManager->method('checkPassword')
			->with(
				$this->equalTo('user'),
				$this->equalTo('wrongpass')
			)->willReturn(false);

		$expected = new DataResponse([], 101);
		$this->assertEquals($expected, $this->controller->personCheck('', ''));
	}

	public function testGetIdentityProofWithNotExistingUser(): void {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('NotExistingUser')
			->willReturn(null);

		$expected = new DataResponse(['Account not found'], 404);
		$this->assertEquals($expected, $this->controller->getIdentityProof('NotExistingUser'));
	}

	public function testGetIdentityProof(): void {
		$user = $this->createMock(IUser::class);
		$key = $this->createMock(Key::class);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('ExistingUser')
			->willReturn($user);
		$this->keyManager
			->expects($this->once())
			->method('getKey')
			->with($user)
			->willReturn($key);
		$key
			->expects($this->once())
			->method('getPublic')
			->willReturn('Existing Users public key');

		$expected = new DataResponse([
			'public' => 'Existing Users public key',
		]);
		$this->assertEquals($expected, $this->controller->getIdentityProof('ExistingUser'));
	}
}
