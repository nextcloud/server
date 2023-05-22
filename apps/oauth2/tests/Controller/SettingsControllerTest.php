<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\OAuth2\Tests\Controller;

use OC\Authentication\Token\IToken;
use OCP\Authentication\Token\IProvider as IAuthTokenProvider;
use OCA\OAuth2\Controller\SettingsController;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Test\TestCase;

/**
 * @group DB
 */
class SettingsControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ClientMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $clientMapper;
	/** @var ISecureRandom|\PHPUnit\Framework\MockObject\MockObject */
	private $secureRandom;
	/** @var AccessTokenMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $accessTokenMapper;
	/** @var IAuthTokenProvider|\PHPUnit\Framework\MockObject\MockObject */
	private $authTokenProvider;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var SettingsController */
	private $settingsController;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var ICrypto|\PHPUnit\Framework\MockObject\MockObject */
	private $crypto;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->accessTokenMapper = $this->createMock(AccessTokenMapper::class);
		$this->authTokenProvider = $this->createMock(IAuthTokenProvider::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')
			->willReturnArgument(0);
		$this->crypto = $this->createMock(ICrypto::class);

		$this->settingsController = new SettingsController(
			'oauth2',
			$this->request,
			$this->clientMapper,
			$this->secureRandom,
			$this->accessTokenMapper,
			$this->l,
			$this->authTokenProvider,
			$this->userManager,
			$this->crypto
		);

	}

	public function testAddClient() {
		$this->secureRandom
			->expects($this->exactly(2))
			->method('generate')
			->with(64, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
			->willReturnOnConsecutiveCalls(
				'MySecret',
				'MyClientIdentifier');

		$this->crypto
			->expects($this->once())
			->method('encrypt')
			->willReturn('MyEncryptedSecret');

		$client = new Client();
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('MySecret');
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->expects($this->once())
			->method('insert')
			->with($this->callback(function (Client $c) {
				return $c->getName() === 'My Client Name' &&
					$c->getRedirectUri() === 'https://example.com/' &&
					$c->getSecret() === 'MyEncryptedSecret' &&
					$c->getClientIdentifier() === 'MyClientIdentifier';
			}))->willReturnCallback(function (Client $c) {
				$c->setId(42);
				return $c;
			});

		$result = $this->settingsController->addClient('My Client Name', 'https://example.com/');
		$this->assertInstanceOf(JSONResponse::class, $result);

		$data = $result->getData();

		$this->assertEquals([
			'id' => 42,
			'name' => 'My Client Name',
			'redirectUri' => 'https://example.com/',
			'clientId' => 'MyClientIdentifier',
			'clientSecret' => 'MySecret',
		], $data);
	}

	public function testDeleteClient() {

		$userManager = \OC::$server->getUserManager();
		// count other users in the db before adding our own
		$count = 0;
		$function = function (IUser $user) use (&$count) {
			if ($user->getLastLogin() > 0) {
				$count++;
			}
		};
		$userManager->callForAllUsers($function);
		$user1 = $userManager->createUser('test101', 'test101');
		$user1->updateLastLoginTimestamp();
		$tokenProviderMock = $this->getMockBuilder(IAuthTokenProvider::class)->getMock();

		// expect one call per user and ensure the correct client name
		$tokenProviderMock
			->expects($this->exactly($count + 1))
			->method('invalidateTokensOfUser')
			->with($this->isType('string'), 'My Client Name');

		$client = new Client();
		$client->setId(123);
		$client->setName('My Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('MySecret');
		$client->setClientIdentifier('MyClientIdentifier');

		$this->clientMapper
			->method('getByUid')
			->with(123)
			->willReturn($client);
		$this->accessTokenMapper
			->expects($this->once())
			->method('deleteByClientId')
			->with(123);
		$this->clientMapper
			->expects($this->once())
			->method('delete')
			->with($client);

		$settingsController = new SettingsController(
			'oauth2',
			$this->request,
			$this->clientMapper,
			$this->secureRandom,
			$this->accessTokenMapper,
			$this->l,
			$tokenProviderMock,
			$userManager,
			$this->crypto
		);

		$result = $settingsController->deleteClient(123);
		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals([], $result->getData());

		$user1->delete();
	}

	public function testInvalidRedirectUri() {
		$result = $this->settingsController->addClient('test', 'invalidurl');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame(['message' => 'Your redirect URL needs to be a full URL for example: https://yourdomain.com/path'], $result->getData());
	}
}
