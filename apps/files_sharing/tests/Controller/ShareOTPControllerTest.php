<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Controller;

use OC\OneTimePassword\OneTimePassword;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Controller\ShareOTPController;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\OneTimePassword\Exceptions\OTPProviderNotFoundException;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\Exceptions\OTPThrottledException;
use OCP\OneTimePassword\IManager as IOTPManager;
use OCP\OneTimePassword\IOneTimePassword;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('OTP')]
class ShareOTPControllerTest extends TestCase {
	private ShareOTPController $controller;
	private IRequest&MockObject $request;
	private IOTPManager&MockObject $otpManager;
	private IShareManager&MockObject $shareManager;
	private LoggerInterface&MockObject $logger;
	private IFactory&MockObject $l10nFactory;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->otpManager = $this->createMock(IOTPManager::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l10nFactory->method('get')->willReturn($this->l10n);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->controller = new ShareOTPController(
			Application::APP_ID,
			$this->request,
			$this->shareManager,
			$this->otpManager,
			$this->logger,
			$this->l10nFactory,
			$this->urlGenerator
		);
	}

	public function testRequest() {
		$token = 'testtoken';
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$otp = new OneTimePassword('mock', 'recipient');
		$share->method('getOneTimePassword')->willReturn($otp);
		$this->urlGenerator->method('linkToRouteAbsolute')->willReturnCallback(function ($name, $params) {
			return 'https://someurl?token=' . ($params['token'] ?? '');
		});
		$this->shareManager->method('getShareByToken')->willReturn($share);

		$this->otpManager->expects($this->once())->method('sendOTP')->with(
			$this->callback(function (IOneTimePassword $otp) {
				$this->assertEquals('mock', $otp->getProviderId());

				return true;
			}),
			$this->callback(function (?string $message) use ($token) {
				$this->assertNotNull($message);
				$this->assertStringContainsString($token, $message);

				return true;
			})
		);

		$this->controller->request($token);
	}

	public function testRequestMissingShare() {
		$token = 'testtoken';
		$this->shareManager->method('getShareByToken')
			->willThrowException(new ShareNotFound('share not found'));

		$response = $this->controller->request($token);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testRequestMissingOTP() {
		$token = 'testtoken';
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getOneTimePassword')->willReturn(null);
		$this->shareManager->method('getShareByToken')->willReturn($share);

		$response = $this->controller->request($token);
		$this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testRequestMissingOTPProvider() {
		$token = 'testtoken';
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$otp = new OneTimePassword('mock', 'recipient');
		$share->method('getOneTimePassword')->willReturn($otp);
		$this->shareManager->method('getShareByToken')->willReturn($share);
		$this->otpManager->method('sendOTP')->willThrowException(new OTPProviderNotFoundException('mock'));

		$response = $this->controller->request($token);
		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testRequestThrottled(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getOneTimePassword')->willReturn(new OneTimePassword('mock', 'someone@example.com'));
		$this->shareManager->method('getShareByToken')->willReturn($share);
		$this->otpManager->method('sendOTP')->willThrowException(new OTPThrottledException('sent recently'));

		$response = $this->controller->request('testtoken');
		$this->assertEquals(Http::STATUS_TOO_MANY_REQUESTS, $response->getStatus());
	}

	public function testRequestSendFailureDoesNotDiscloseRecipient(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getOneTimePassword')->willReturn(new OneTimePassword('mock', 'someone@example.com'));
		$this->shareManager->method('getShareByToken')->willReturn($share);
		$this->otpManager->method('sendOTP')->willThrowException(new OTPSendException("Failed to send one time password to 'mock(someone@example.com'): SMTP down"));

		$response = $this->controller->request('testtoken');
		$this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertStringNotContainsString('someone@example.com', json_encode($response->getData()));
		$this->assertStringNotContainsString('SMTP', json_encode($response->getData()));
	}

	public function testRequestMissingOTPProviderMessage(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getOneTimePassword')->willReturn(new OneTimePassword('mock', 'someone@example.com'));
		$this->shareManager->method('getShareByToken')->willReturn($share);
		$this->otpManager->method('sendOTP')->willThrowException(new OTPProviderNotFoundException('mock'));

		$response = $this->controller->request('testtoken');
		$this->assertEquals(['error' => 'No OTP provider found for id mock'], $response->getData());
	}

	public function testListProviders() {
		$mockProvider = $this->createMock(IOneTimePasswordProvider::class);
		$mockProvider->method('getProviderId')->willReturn('mock');
		$mockProvider->method('getName')->willReturn('mockname');
		$mockProvider->method('getDescription')->willReturn('mockdescription');
		$mockProvider->method('getRecipientPattern')->willReturn('mockpattern');
		$this->otpManager->method('getOTPProviders')->willReturn([$mockProvider]);

		$providers = $this->controller->listProviders();
		$expected = [
			'id' => 'mock',
			'name' => 'mockname',
			'description' => 'mockdescription',
			'recipientPattern' => 'mockpattern'
		];

		$this->assertEquals(Http::STATUS_OK, $providers->getStatus());
		$this->assertEquals([$expected], $providers->getData());
	}
}
