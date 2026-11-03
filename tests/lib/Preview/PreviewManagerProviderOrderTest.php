<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Preview\GeneratorHelper;
use OC\Preview\HEIC;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\JPEG;
use OC\PreviewManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IBinaryFinder;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Test\TestCase;

class PreviewManagerProviderOrderTest extends TestCase {
	public function testGetProvidersMapFollowsEnabledPreviewProvidersOrder(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => match ($key) {
			'enable_previews' => true,
			default => $default,
		});
		$config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) {
			if ($key === 'enabledPreviewProviders') {
				return [HEIC::class, Imaginary::class, JPEG::class];
			}
			return $default;
		});
		$config->method('getSystemValueString')->willReturn('');

		$imagick = $this->createMock(IMagickSupport::class);
		$imagick->method('hasExtension')->willReturn(true);
		$imagick->method('supportsFormat')->willReturn(true);

		$finder = $this->createMock(IBinaryFinder::class);
		$finder->method('findBinaryPath')->willReturn(false);

		$coordinator = $this->createMock(Coordinator::class);
		$coordinator->method('getRegistrationContext')->willReturn(null);

		$manager = new PreviewManager(
			$config,
			$this->createMock(IRootFolder::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(GeneratorHelper::class),
			null,
			$coordinator,
			$this->createMock(ContainerInterface::class),
			$finder,
			$imagick,
		);

		$keys = array_keys($manager->getProviders());
		$heicIndex = array_search('/image\/(x-)?hei(f|c)/', $keys, true);
		$imaginaryIndex = array_search(Imaginary::supportedMimeTypes(), $keys, true);
		$jpegIndex = array_search('/image\/jpeg/', $keys, true);

		$this->assertNotFalse($heicIndex);
		$this->assertNotFalse($imaginaryIndex);
		$this->assertNotFalse($jpegIndex);
		$this->assertLessThan($imaginaryIndex, $heicIndex);
		$this->assertLessThan($jpegIndex, $imaginaryIndex);
	}
}
