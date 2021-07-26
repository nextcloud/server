<?php
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\Controller;

use OCP\AppFramework\PublicShareController;
use OCP\IRequest;
use OCP\ISession;

class TestController extends PublicShareController {

	/** @var string */
	private $hash;

	/** @var bool */
	private $isProtected;

	public function __construct(string $appName, IRequest $request, ISession $session, string $hash, bool $isProtected) {
		parent::__construct($appName, $request, $session);

		$this->hash = $hash;
		$this->isProtected = $isProtected;
	}

	protected function getPasswordHash(): string {
		return $this->hash;
	}

	public function isValidToken(): bool {
		return false;
	}

	protected function isPasswordProtected(): bool {
		return $this->isProtected;
	}
}

class PublicShareControllerTest extends \Test\TestCase {

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
	}

	public function testGetToken() {
		$controller = new TestController('app', $this->request, $this->session, 'hash', false);

		$controller->setToken('test');
		$this->assertEquals('test', $controller->getToken());
	}

	public function dataIsAuthenticated() {
		return [
			[false, 'token1', 'token1', 'hash1', 'hash1',  true],
			[false, 'token1', 'token1', 'hash1', 'hash2',  true],
			[false, 'token1', 'token2', 'hash1', 'hash1',  true],
			[false, 'token1', 'token2', 'hash1', 'hash2',  true],
			[ true, 'token1', 'token1', 'hash1', 'hash1',  true],
			[ true, 'token1', 'token1', 'hash1', 'hash2', false],
			[ true, 'token1', 'token2', 'hash1', 'hash1', false],
			[ true, 'token1', 'token2', 'hash1', 'hash2', false],
		];
	}

	/**
	 * @dataProvider dataIsAuthenticated
	 */
	public function testIsAuthenticatedNotPasswordProtected(bool $protected, string $token1, string $token2, string $hash1, string $hash2, bool $expected) {
		$controller = new TestController('app', $this->request, $this->session, $hash2, $protected);

		$this->session->method('get')
			->willReturnMap([
				['public_link_authenticated_token', $token1],
				['public_link_authenticated_password_hash', $hash1],
			]);

		$controller->setToken($token2);

		$this->assertEquals($expected, $controller->isAuthenticated());
	}
}
