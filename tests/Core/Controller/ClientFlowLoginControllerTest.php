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

namespace Tests\Core\Controller;

use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Controller\ClientFlowLoginController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Test\TestCase;

class ClientFlowLoginControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var IProvider|\PHPUnit_Framework_MockObject_MockObject */
	private $tokenProvider;
	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $random;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ClientFlowLoginController */
	private $clientFlowLoginController;

	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->defaults = $this->createMock(Defaults::class);
		$this->session = $this->createMock(ISession::class);
		$this->tokenProvider = $this->createMock(IProvider::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->clientFlowLoginController = new ClientFlowLoginController(
			'core',
			$this->request,
			$this->userSession,
			$this->l10n,
			$this->defaults,
			$this->session,
			$this->tokenProvider,
			$this->random,
			$this->urlGenerator
		);
	}

	public function testShowAuthPickerPageNotAuthenticated() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(true);

		$expected = new TemplateResponse(
			'core',
			'403',
			[
				'file' => 'Auth flow can only be started unauthenticated.',
			],
			'guest'
		);
		$this->assertEquals($expected, $this->clientFlowLoginController->showAuthPickerPage());
	}

	public function testShowAuthPickerPage() {
		$this->userSession
			->expects($this->once())
			->method('isLoggedIn')
			->willReturn(false);
		$this->random
			->expects($this->once())
			->method('generate')
			->with(
				64,
				ISecureRandom::CHAR_LOWER.ISecureRandom::CHAR_UPPER.ISecureRandom::CHAR_DIGITS
			)
			->willReturn('StateToken');
		$this->session
			->expects($this->once())
			->method('set')
			->with('client.flow.state.token', 'StateToken');
		$this->request
			->expects($this->exactly(2))
			->method('getHeader')
			->with('USER_AGENT')
			->willReturn('Mac OS X Sync Client');
		$this->defaults
			->expects($this->once())
			->method('getName')
			->willReturn('ExampleCloud');
		$this->request
			->expects($this->once())
			->method('getServerHost')
			->willReturn('example.com');

		$expected = new TemplateResponse(
			'core',
			'loginflow/authpicker',
			[
				'client' => 'Mac OS X Sync Client',
				'instanceName' => 'ExampleCloud',
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => 'StateToken',
				'serverHost' => 'example.com',
			],
			'guest'
		);
		$this->assertEquals($expected, $this->clientFlowLoginController->showAuthPickerPage());
	}

	public function testRedirectPageWithInvalidToken() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('OtherToken');

		$expected = new TemplateResponse(
			'core',
			'403',
			[
				'file' => 'State token does not match',
			],
			'guest'
		);
		$expected->setStatus(Http::STATUS_FORBIDDEN);
		$this->assertEquals($expected, $this->clientFlowLoginController->redirectPage('MyStateToken'));
	}

	public function testRedirectPageWithoutToken() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn(null);

		$expected = new TemplateResponse(
			'core',
			'403',
			[
				'file' => 'State token does not match',
			],
			'guest'
		);
		$expected->setStatus(Http::STATUS_FORBIDDEN);
		$this->assertEquals($expected, $this->clientFlowLoginController->redirectPage('MyStateToken'));
	}

	public function testRedirectPage() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('MyStateToken');

		$expected = new TemplateResponse(
			'core',
			'loginflow/redirect',
			[
				'urlGenerator' => $this->urlGenerator,
				'stateToken' => 'MyStateToken',
			],
			'empty'
		);
		$this->assertEquals($expected, $this->clientFlowLoginController->redirectPage('MyStateToken'));
	}

	public function testGenerateAppPasswordWithInvalidToken() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('OtherToken');
		$this->session
			->expects($this->once())
			->method('remove')
			->with('client.flow.state.token');

		$expected = new TemplateResponse(
			'core',
			'403',
			[
				'file' => 'State token does not match',
			],
			'guest'
		);
		$expected->setStatus(Http::STATUS_FORBIDDEN);
		$this->assertEquals($expected, $this->clientFlowLoginController->generateAppPassword('MyStateToken'));
	}

	public function testGenerateAppPasswordWithSessionNotAvailableException() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('MyStateToken');
		$this->session
			->expects($this->once())
			->method('remove')
			->with('client.flow.state.token');
		$this->session
			->expects($this->once())
			->method('getId')
			->willThrowException(new SessionNotAvailableException());

		$expected = new Http\Response();
		$expected->setStatus(Http::STATUS_FORBIDDEN);
		$this->assertEquals($expected, $this->clientFlowLoginController->generateAppPassword('MyStateToken'));
	}

	public function testGenerateAppPasswordWithInvalidTokenException() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('MyStateToken');
		$this->session
			->expects($this->once())
			->method('remove')
			->with('client.flow.state.token');
		$this->session
			->expects($this->once())
			->method('getId')
			->willReturn('SessionId');
		$this->tokenProvider
			->expects($this->once())
			->method('getToken')
			->with('SessionId')
			->willThrowException(new InvalidTokenException());

		$expected = new Http\Response();
		$expected->setStatus(Http::STATUS_FORBIDDEN);
		$this->assertEquals($expected, $this->clientFlowLoginController->generateAppPassword('MyStateToken'));
	}

	public function testGeneratePasswordWithPassword() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('MyStateToken');
		$this->session
			->expects($this->once())
			->method('remove')
			->with('client.flow.state.token');
		$this->session
			->expects($this->once())
			->method('getId')
			->willReturn('SessionId');
		$myToken = $this->createMock(IToken::class);
		$myToken
			->expects($this->once())
			->method('getLoginName')
			->willReturn('MyLoginName');
		$this->tokenProvider
			->expects($this->once())
			->method('getToken')
			->with('SessionId')
			->willReturn($myToken);
		$this->tokenProvider
			->expects($this->once())
			->method('getPassword')
			->with($myToken, 'SessionId')
			->willReturn('MyPassword');
		$this->random
			->expects($this->once())
			->method('generate')
			->with(72)
			->willReturn('MyGeneratedToken');
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->tokenProvider
			->expects($this->once())
			->method('generateToken')
			->with(
				'MyGeneratedToken',
				'MyUid',
				'MyLoginName',
				'MyPassword',
				'unknown',
				IToken::PERMANENT_TOKEN,
				IToken::DO_NOT_REMEMBER
			);
		$this->request
			->expects($this->once())
			->method('getServerHost')
			->willReturn('example.com');

		$expected = new Http\RedirectResponse('nc://MyLoginName:MyGeneratedToken@example.com');
		$this->assertEquals($expected, $this->clientFlowLoginController->generateAppPassword('MyStateToken'));
	}

	public function testGeneratePasswordWithoutPassword() {
		$this->session
			->expects($this->once())
			->method('get')
			->with('client.flow.state.token')
			->willReturn('MyStateToken');
		$this->session
			->expects($this->once())
			->method('remove')
			->with('client.flow.state.token');
		$this->session
			->expects($this->once())
			->method('getId')
			->willReturn('SessionId');
		$myToken = $this->createMock(IToken::class);
		$myToken
			->expects($this->once())
			->method('getLoginName')
			->willReturn('MyLoginName');
		$this->tokenProvider
			->expects($this->once())
			->method('getToken')
			->with('SessionId')
			->willReturn($myToken);
		$this->tokenProvider
			->expects($this->once())
			->method('getPassword')
			->with($myToken, 'SessionId')
			->willThrowException(new PasswordlessTokenException());
		$this->random
			->expects($this->once())
			->method('generate')
			->with(72)
			->willReturn('MyGeneratedToken');
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('MyUid');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->tokenProvider
			->expects($this->once())
			->method('generateToken')
			->with(
				'MyGeneratedToken',
				'MyUid',
				'MyLoginName',
				null,
				'unknown',
				IToken::PERMANENT_TOKEN,
				IToken::DO_NOT_REMEMBER
			);
		$this->request
			->expects($this->once())
			->method('getServerHost')
			->willReturn('example.com');

		$expected = new Http\RedirectResponse('nc://MyLoginName:MyGeneratedToken@example.com');
		$this->assertEquals($expected, $this->clientFlowLoginController->generateAppPassword('MyStateToken'));
	}
}
