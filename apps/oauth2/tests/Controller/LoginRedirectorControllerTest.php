<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Tests\Controller;

use OC\Core\Controller\ClientFlowLoginController;
use OCA\OAuth2\Controller\LoginRedirectorController;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class LoginRedirectorControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IURLGenerator&MockObject $urlGenerator;
	private ClientMapper&MockObject $clientMapper;
	private ISession&MockObject $session;
	private IL10N&MockObject $l;
	private ISecureRandom&MockObject $random;
	private IAppConfig&MockObject $appConfig;

	private LoginRedirectorController $loginRedirectorController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->session = $this->createMock(ISession::class);
		$this->l = $this->createMock(IL10N::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->loginRedirectorController = new LoginRedirectorController(
			'oauth2',
			$this->request,
			$this->urlGenerator,
			$this->clientMapper,
			$this->session,
			$this->l,
			$this->random,
			$this->appConfig,
		);
	}

	public function testAuthorize(): void {
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

	public function testAuthorizeSkipPicker(): void {
		$client = new Client();
		$client->setName('MyClientName');
		$client->setClientIdentifier('MyClientIdentifier');
		$this->clientMapper
			->expects($this->once())
			->method('getByIdentifier')
			->with('MyClientId')
			->willReturn($client);
		$this->session
			->expects(static::exactly(2))
			->method('set')
			->willReturnCallback(function (string $key, string $value): void {
				switch ([$key, $value]) {
					case ['oauth.state', 'MyState']:
					case [ClientFlowLoginController::STATE_NAME, 'MyStateToken']:
						/* Expected */
						break;
					default:
						throw new LogicException();
				}
			});
		$this->appConfig
			->expects(static::once())
			->method('getValueArray')
			->with('oauth2', 'skipAuthPickerApplications', [])
			->willReturn(['MyClientName']);
		$this->random
			->expects(static::once())
			->method('generate')
			->willReturn('MyStateToken');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRouteAbsolute')
			->with(
				'core.ClientFlowLogin.grantPage',
				[
					'stateToken' => 'MyStateToken',
					'clientIdentifier' => 'MyClientIdentifier',
				]
			)
			->willReturn('https://example.com/?clientIdentifier=foo');

		$expected = new RedirectResponse('https://example.com/?clientIdentifier=foo');
		$this->assertEquals($expected, $this->loginRedirectorController->authorize('MyClientId', 'MyState', 'code'));
	}

	public function testAuthorizeWrongResponseType(): void {
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

	public function testClientNotFound(): void {
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
