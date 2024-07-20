<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
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
	/** @var IConfig|MockObject */
	private $config;
	/** @var BlockLegacyClientPlugin */
	private $blockLegacyClientVersionPlugin;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->blockLegacyClientVersionPlugin = new BlockLegacyClientPlugin($this->config);
	}

	public function oldDesktopClientProvider(): array {
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
		$this->expectExceptionMessage('Unsupported client version.');

		/** @var RequestInterface|MockObject $request */
		$request = $this->createMock('\Sabre\HTTP\RequestInterface');
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
