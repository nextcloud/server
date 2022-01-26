<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * @group DB
 */
class BearerAuthTest extends TestCase {
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var BearerAuth */
	private $bearerAuth;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$session = $this->createMock(ISession::class);
		$request = $this->createMock(IRequest::class);

		$this->bearerAuth = new BearerAuth(
			$this->userSession,
			$session,
			$request
		);
	}

	public function testValidateBearerTokenNotLoggedIn() {
		$this->assertFalse($this->bearerAuth->validateBearerToken('Token'));
	}

	public function testValidateBearerToken() {
		$this->userSession
			->expects($this->exactly(2))
			->method('isLoggedIn')
			->willReturnOnConsecutiveCalls(false, true);
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->assertSame('principals/users/admin', $this->bearerAuth->validateBearerToken('Token'));
	}

	public function testChallenge() {
		/** @var MockObject|RequestInterface $request */
		$request = $this->createMock(RequestInterface::class);
		/** @var MockObject|ResponseInterface $response */
		$response = $this->createMock(ResponseInterface::class);
		$result = $this->bearerAuth->challenge($request, $response);
		$this->assertEmpty($result);
	}
}
