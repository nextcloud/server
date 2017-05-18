<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OAuth2\Tests\Controller;

use OC\Authentication\Token\DefaultTokenMapper;
use OCA\OAuth2\Controller\SettingsController;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ClientMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $clientMapper;
	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $secureRandom;
	/** @var AccessTokenMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $accessTokenMapper;
	/** @var DefaultTokenMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $defaultTokenMapper;
	/** @var SettingsController */
	private $settingsController;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->accessTokenMapper = $this->createMock(AccessTokenMapper::class);
		$this->defaultTokenMapper = $this->createMock(DefaultTokenMapper::class);

		$this->settingsController = new SettingsController(
			'oauth2',
			$this->request,
			$this->urlGenerator,
			$this->clientMapper,
			$this->secureRandom,
			$this->accessTokenMapper,
			$this->defaultTokenMapper
		);
	}

	public function testAddClient() {
		$this->secureRandom
			->expects($this->at(0))
			->method('generate')
			->with(64, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('MySecret');
		$this->secureRandom
			->expects($this->at(1))
			->method('generate')
			->with(64, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturn('MyClientIdentifier');

		$client = new Client();
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('MySecret');
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->expects($this->once())
			->method('insert')
			->with($client);

		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/index.php/settings/admin/security')
			->willReturn('https://example.com/index.php/settings/admin/security');

		$expected = new RedirectResponse('https://example.com/index.php/settings/admin/security');
		$this->assertEquals($expected, $this->settingsController->addClient('My Client Name', 'https://example.com/'));
	}

	public function testDeleteClient() {
		$client = new Client();
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('MySecret');
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->expects($this->at(0))
			->method('getByUid')
			->with(123)
			->willReturn($client);
		$this->accessTokenMapper
			->expects($this->once())
			->method('deleteByClientId')
			->with(123);
		$this->defaultTokenMapper
			->expects($this->once())
			->method('deleteByName')
			->with('My Client Name');
		$this->clientMapper
			->expects($this->at(1))
			->method('delete')
			->with($client);

		$this->urlGenerator
			->expects($this->once())
			->method('getAbsoluteURL')
			->with('/index.php/settings/admin/security')
			->willReturn('https://example.com/index.php/settings/admin/security');

		$expected = new RedirectResponse('https://example.com/index.php/settings/admin/security');
		$this->assertEquals($expected, $this->settingsController->deleteClient(123));
	}
}
