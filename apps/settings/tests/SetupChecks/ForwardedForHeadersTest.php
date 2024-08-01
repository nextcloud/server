<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\ForwardedForHeaders;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use Test\TestCase;

class ForwardedForHeadersTest extends TestCase {
	private IL10N $l10n;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private IRequest $request;
	private ForwardedForHeaders $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->check = new ForwardedForHeaders(
			$this->l10n,
			$this->config,
			$this->urlGenerator,
			$this->request,
		);
	}

	/**
	 * @dataProvider dataForwardedForHeadersWorking
	 */
	public function testForwardedForHeadersWorking(array $trustedProxies, string $remoteAddrNotForwarded, string $remoteAddr, string $result): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies', [])
			->willReturn($trustedProxies);
		$this->request->expects($this->atLeastOnce())
			->method('getHeader')
			->willReturnMap([
				['REMOTE_ADDR', $remoteAddrNotForwarded],
				['X-Forwarded-Host', '']
			]);
		$this->request->expects($this->any())
			->method('getRemoteAddress')
			->willReturn($remoteAddr);

		$this->assertEquals(
			$result,
			$this->check->run()->getSeverity()
		);
	}

	public function dataForwardedForHeadersWorking(): array {
		return [
			// description => trusted proxies, getHeader('REMOTE_ADDR'), getRemoteAddr, expected result
			'no trusted proxies' => [[], '2.2.2.2', '2.2.2.2', SetupResult::SUCCESS],
			'trusted proxy, remote addr not trusted proxy' => [['1.1.1.1'], '2.2.2.2', '2.2.2.2', SetupResult::SUCCESS],
			'trusted proxy, remote addr is trusted proxy, x-forwarded-for working' => [['1.1.1.1'], '1.1.1.1', '2.2.2.2', SetupResult::SUCCESS],
			'trusted proxy, remote addr is trusted proxy, x-forwarded-for not set' => [['1.1.1.1'], '1.1.1.1', '1.1.1.1', SetupResult::WARNING],
		];
	}

	public function testForwardedHostPresentButTrustedProxiesNotAnArray(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies', [])
			->willReturn('1.1.1.1');
		$this->request->expects($this->atLeastOnce())
			->method('getHeader')
			->willReturnMap([
				['REMOTE_ADDR', '1.1.1.1'],
				['X-Forwarded-Host', 'nextcloud.test']
			]);
		$this->request->expects($this->any())
			->method('getRemoteAddress')
			->willReturn('1.1.1.1');

		$this->assertEquals(
			SetupResult::ERROR,
			$this->check->run()->getSeverity()
		);
	}

	public function testForwardedHostPresentButTrustedProxiesEmpty(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_proxies', [])
			->willReturn([]);
		$this->request->expects($this->atLeastOnce())
			->method('getHeader')
			->willReturnMap([
				['REMOTE_ADDR', '1.1.1.1'],
				['X-Forwarded-Host', 'nextcloud.test']
			]);
		$this->request->expects($this->any())
			->method('getRemoteAddress')
			->willReturn('1.1.1.1');

		$this->assertEquals(
			SetupResult::ERROR,
			$this->check->run()->getSeverity()
		);
	}
}
