<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\BlockLegacyClientPlugin;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use Sabre\HTTP\RequestInterface;

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
