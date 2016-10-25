<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\Security\CSP;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\Security\CSRF\CsrfToken;
use OC\Security\CSRF\CsrfTokenManager;
use OCP\IRequest;
use Test\TestCase;

class ContentSecurityPolicyNonceManagerTest extends TestCase  {
	/** @var CsrfTokenManager */
	private $csrfTokenManager;
	/** @var ContentSecurityPolicyNonceManager */
	private $nonceManager;

	public function setUp() {
		$this->csrfTokenManager = $this->createMock(CsrfTokenManager::class);
		$this->nonceManager = new ContentSecurityPolicyNonceManager(
			$this->csrfTokenManager,
			$this->createMock(IRequest::class)
		);
	}

	public function testGetNonce() {
		$token = $this->createMock(CsrfToken::class);
		$token
			->expects($this->once())
			->method('getEncryptedValue')
			->willReturn('MyToken');

		$this->csrfTokenManager
			->expects($this->once())
			->method('getToken')
			->willReturn($token);

		$this->assertSame('TXlUb2tlbg==', $this->nonceManager->getNonce());
		$this->assertSame('TXlUb2tlbg==', $this->nonceManager->getNonce());
	}
}
