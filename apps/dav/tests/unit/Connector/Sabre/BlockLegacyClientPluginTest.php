<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCA\Theming\ThemingDefaults;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

/**
 * Class BlockLegacyClientPluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class BlockLegacyClientPluginTest extends TestCase {

	private IConfig&MockObject $config;
	private ThemingDefaults&MockObject $themingDefaults;
	private BlockLegacyClientPlugin $blockLegacyClientVersionPlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->blockLegacyClientVersionPlugin = new BlockLegacyClientPlugin(
			$this->config,
			$this->themingDefaults,
		);
	}

	public static function oldDesktopClientProvider(): array {
		return [
			['Mozilla/5.0 (Windows) mirall/1.5.0'],
			['Mozilla/5.0 (Bogus Text) mirall/1.6.9'],
		];
	}

	/**
	 * @dataProvider oldDesktopClientProvider
	 */
	public function testBeforeHandlerException(string $userAgent): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->themingDefaults
			->expects($this->once())
			->method('getSyncClientUrl')
			->willReturn('https://nextcloud.com/install/#install-clients');

		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('minimum.supported.desktop.version', '2.3.0')
			->willReturn('1.7.0');

		$this->expectExceptionMessage('This version of the client is unsupported. Upgrade to <a href="https://nextcloud.com/install/#install-clients">version 1.7.0 or later</a>.');

		/** @var RequestInterface|MockObject $request */
		$request = $this->createMock('\Sabre\HTTP\RequestInterface');
		$request
			->expects($this->once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn($userAgent);


		$this->blockLegacyClientVersionPlugin->beforeHandler($request);
	}

	/**
	 * Ensure that there is no room for XSS attack through configured URL / version
	 * @dataProvider oldDesktopClientProvider
	 */
	public function testBeforeHandlerExceptionPreventXSSAttack(string $userAgent): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->themingDefaults
			->expects($this->once())
			->method('getSyncClientUrl')
			->willReturn('https://example.com"><script>alter("hacked");</script>');

		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('minimum.supported.desktop.version', '2.3.0')
			->willReturn('1.7.0 <script>alert("unsafe")</script>');

		$this->expectExceptionMessage('This version of the client is unsupported. Upgrade to <a href="https://example.com&quot;&gt;&lt;script&gt;alter(&quot;hacked&quot;);&lt;/script&gt;">version 1.7.0 &lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt; or later</a>.');

		/** @var RequestInterface|MockObject $request */
		$request = $this->createMock('\Sabre\HTTP\RequestInterface');
		$request
			->expects($this->once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn($userAgent);


		$this->blockLegacyClientVersionPlugin->beforeHandler($request);
	}

	public function newAndAlternateDesktopClientProvider(): array {
		return [
			['Mozilla/5.0 (Windows) mirall/1.7.0'],
			['Mozilla/5.0 (Bogus Text) mirall/1.9.3'],
			['Mozilla/5.0 (Not Our Client But Old Version) LegacySync/1.1.0'],
		];
	}

	/**
	 * @dataProvider newAndAlternateDesktopClientProvider
	 */
	public function testBeforeHandlerSuccess(string $userAgent): void {
		/** @var RequestInterface|MockObject $request */
		$request = $this->createMock(RequestInterface::class);
		$request
			->expects($this->once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn($userAgent);

		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('minimum.supported.desktop.version', '2.3.0')
			->willReturn('1.7.0');

		$this->blockLegacyClientVersionPlugin->beforeHandler($request);
	}

	public function testBeforeHandlerNoUserAgent(): void {
		/** @var RequestInterface|MockObject $request */
		$request = $this->createMock(RequestInterface::class);
		$request
			->expects($this->once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn(null);
		$this->blockLegacyClientVersionPlugin->beforeHandler($request);
	}
}
