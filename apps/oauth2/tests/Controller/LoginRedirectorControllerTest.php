<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Tests\Controller;

use OCA\OAuth2\Controller\LoginRedirectorController;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use Test\TestCase;

/**
 * @group DB
 */
class LoginRedirectorControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var ClientMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $clientMapper;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var LoginRedirectorController */
	private $loginRedirectorController;
	/** @var IL10N */
	private $l;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->session = $this->createMock(ISession::class);
		$this->l = $this->createMock(IL10N::class);

		$this->loginRedirectorController = new LoginRedirectorController(
			'oauth2',
			$this->request,
			$this->urlGenerator,
			$this->clientMapper,
			$this->session,
			$this->l
		);
	}

	public function testAuthorize() {
		$client = new Client();
		$client->setClientIdentifier('MyClientIdentifier');
		$this->clientMapper
			->expects($this->once())
			->method('getByIdentifier')
			->with('MyClientId')
			->willReturn($client);
		$this->session
			->expects($this->once())
			->method('set')
			->with('oauth.state', 'MyState');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with(
				'core.ClientFlowLogin.showAuthPickerPage',
				[
					'clientIdentifier' => 'MyClientIdentifier',
				]
			)
			->willReturn('https://example.com/?clientIdentifier=foo');

		$expected = new RedirectResponse('https://example.com/?clientIdentifier=foo');
		$this->assertEquals($expected, $this->loginRedirectorController->authorize('MyClientId', 'MyState', 'code'));
	}

	public function testAuthorizeWrongResponseType() {
		$client = new Client();
		$client->setClientIdentifier('MyClientIdentifier');
		$client->setRedirectUri('http://foo.bar');
		$this->clientMapper
			->expects($this->once())
			->method('getByIdentifier')
			->with('MyClientId')
			->willReturn($client);
		$this->session
			->expects($this->never())
			->method('set');


		$expected = new RedirectResponse('http://foo.bar?error=unsupported_response_type&state=MyState');
		$this->assertEquals($expected, $this->loginRedirectorController->authorize('MyClientId', 'MyState', 'wrongcode'));
	}

	public function testClientNotFound() {
		$clientNotFound = new ClientNotFoundException('could not find client test123', 0);
		$this->clientMapper
			->expects($this->once())
			->method('getByIdentifier')
			->willThrowException($clientNotFound);
		$this->session
			->expects($this->never())
			->method('set');

		$response = $this->loginRedirectorController->authorize('MyClientId', 'MyState', 'wrongcode');
		$this->assertInstanceOf(TemplateResponse::class, $response);

		/** @var TemplateResponse $response */
		$this->assertEquals('404', $response->getTemplateName());
		$this->assertEquals('guest', $response->getRenderAs());
	}
}
