<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Preview\GeneratorHelper;
use OC\Preview\IMagickSupport;
use OC\Preview\Movie;
use OC\Preview\MovieRemote;
use OC\PreviewManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IBinaryFinder;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PreviewManagerTest extends TestCase {
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private PreviewManager $previewManager;
	private array $systemConfig = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValueBool')->willReturn(true);
		$this->config->method('getSystemValue')->willReturnCallback(fn (string $key, mixed $default) => $this->systemConfig[$key] ?? $default);
		$this->config->method('getSystemValueString')->willReturnCallback(fn (string $key, string $default) => $this->systemConfig[$key] ?? $default);

		$this->logger = $this->createMock(LoggerInterface::class);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->with(LoggerInterface::class)->willReturn($this->logger);
		$binaryFinder = $this->createMock(IBinaryFinder::class);
		$binaryFinder->method('findBinaryPath')->willReturn(false);

		$this->previewManager = new PreviewManager(
			$this->config,
			$this->createMock(IRootFolder::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(GeneratorHelper::class),
			null,
			$this->createMock(Coordinator::class),
			$container,
			$binaryFinder,
			$this->createMock(IMagickSupport::class),
		);
	}

	/**
	 * @return list<class-string>
	 */
	private function getVideoProviders(): array {
		return array_map(fn (\Closure $provider): string => $provider()::class, $this->previewManager->getProviders()['/video\/.*/'] ?? []);
	}

	public function testMovieRemoteIsTriedBeforeMovie(): void {
		$this->systemConfig = [
			'enabledPreviewProviders' => [Movie::class, MovieRemote::class],
			'preview_ffmpeg_path' => '/usr/bin/ffmpeg',
			'preview_ffmpeg_address' => 'ffmpeg-over-ip:5050',
		];

		$this->assertSame([MovieRemote::class, Movie::class], $this->getVideoProviders());
	}

	public function testMovieRemoteWithoutAddress(): void {
		$this->systemConfig = [
			'enabledPreviewProviders' => [MovieRemote::class],
		];
		$this->logger->expects($this->once())
			->method('error')
			->with($this->stringContains('preview_ffmpeg_address'));

		$this->assertSame([], $this->getVideoProviders());
		$this->assertFalse($this->previewManager->isMimeSupported('video/mp4'));
	}
}
