<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests\Controller;

use OC\BackgroundJob\JobList;
use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Federation\Controller\OCSAuthAPIController;
use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OCSAuthAPIControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private ISecureRandom&MockObject $secureRandom;
	private JobList&MockObject $jobList;
	private TrustedServers&MockObject $trustedServers;
	private DbHandler&MockObject $dbHandler;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $timeFactory;
	private IThrottler&MockObject $throttler;
	private OCSAuthAPIController $ocsAuthApi;

	/** @var int simulated timestamp */
	private int $currentTime = 1234567;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->trustedServers = $this->createMock(TrustedServers::class);
		$this->dbHandler = $this->createMock(DbHandler::class);
		$this->jobList = $this->createMock(JobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->throttler = $this->createMock(IThrottler::class);

		$this->ocsAuthApi = new OCSAuthAPIController(
			'federation',
			$this->request,
			$this->secureRandom,
			$this->jobList,
			$this->trustedServers,
			$this->dbHandler,
			$this->logger,
			$this->timeFactory,
			$this->throttler
		);

		$this->timeFactory->method('getTime')
			->willReturn($this->currentTime);
	}

	/**
	 * @dataProvider dataTestRequestSharedSecret
	 */
	public function testRequestSharedSecret(string $token, string $localToken, bool $isTrustedServer, bool $ok): void {
		$url = 'url';

		$this->trustedServers
			->expects($this->once())
			->method('isTrustedServer')->with($url)->willReturn($isTrustedServer);
		$this->dbHandler->expects($this->any())
			->method('getToken')->with($url)->willReturn($localToken);

		if ($ok) {
			$this->jobList->expects($this->once())->method('add')
				->with(GetSharedSecret::class, ['url' => $url, 'token' => $token, 'created' => $this->currentTime]);
		} else {
			$this->jobList->expects($this->never())->method('add');
			$this->jobList->expects($this->never())->method('remove');
			if (!$isTrustedServer) {
				$this->throttler->expects($this->once())
					->method('registerAttempt')
					->with('federationSharedSecret');
			}
		}


		try {
			$this->ocsAuthApi->requestSharedSecret($url, $token);
			$this->assertTrue($ok);
		} catch (OCSForbiddenException $e) {
			$this->assertFalse($ok);
		}
	}

	public static function dataTestRequestSharedSecret(): array {
		return [
			['token2', 'token1', true, true],
			['token1', 'token2', false, false],
			['token1', 'token2', true, false],
		];
	}

	/**
	 * @dataProvider dataTestGetSharedSecret
	 */
	public function testGetSharedSecret(bool $isTrustedServer, bool $isValidToken, bool $ok): void {
		$url = 'url';
		$token = 'token';

		/** @var OCSAuthAPIController&MockObject $ocsAuthApi */
		$ocsAuthApi = $this->getMockBuilder(OCSAuthAPIController::class)
			->setConstructorArgs(
				[
					'federation',
					$this->request,
					$this->secureRandom,
					$this->jobList,
					$this->trustedServers,
					$this->dbHandler,
					$this->logger,
					$this->timeFactory,
					$this->throttler
				]
			)
			->onlyMethods(['isValidToken'])
			->getMock();

		$this->trustedServers
			->expects($this->any())
			->method('isTrustedServer')->with($url)->willReturn($isTrustedServer);
		$ocsAuthApi->expects($this->any())
			->method('isValidToken')->with($url, $token)->willReturn($isValidToken);

		if ($ok) {
			$this->secureRandom->expects($this->once())->method('generate')->with(32)
				->willReturn('secret');
			$this->trustedServers->expects($this->once())
				->method('addSharedSecret')->with($url, 'secret');
		} else {
			$this->secureRandom->expects($this->never())->method('generate');
			$this->trustedServers->expects($this->never())->method('addSharedSecret');
			$this->throttler->expects($this->once())
				->method('registerAttempt')
				->with('federationSharedSecret');
		}

		try {
			$result = $ocsAuthApi->getSharedSecret($url, $token);
			$this->assertTrue($ok);
			$data = $result->getData();
			$this->assertSame('secret', $data['sharedSecret']);
		} catch (OCSForbiddenException $e) {
			$this->assertFalse($ok);
		}
	}

	public static function dataTestGetSharedSecret(): array {
		return [
			[true, true, true],
			[false, true, false],
			[true, false, false],
			[false, false, false],
		];
	}
}
