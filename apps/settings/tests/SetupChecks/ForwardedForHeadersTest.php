<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
