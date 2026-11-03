<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Tests\SetupChecks;

use OCA\Theming\ImageManager;
use OCA\Theming\SetupChecks\PhpImagickModule;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PhpImagickModuleTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private ImageManager&MockObject $imageManager;
	private PhpImagickModule $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->check = new PhpImagickModule($this->l10n, $this->urlGenerator, $this->imageManager);
	}

	public static function dataRun(): array {
		return [
			'imagick with svg logo' => [true, true, false, 'image/svg+xml', SetupResult::SUCCESS, false, false],
			'no imagick' => [false, false, false, '', SetupResult::INFO, true, false],
			'imagick without svg support' => [false, true, false, '', SetupResult::INFO, true, false],
			'imagick without png support' => [true, false, false, '', SetupResult::INFO, true, false],
			'no imagick with favicon' => [false, false, true, '', SetupResult::SUCCESS, false, false],
			'no imagick with favicon and png logo' => [false, false, true, 'image/png', SetupResult::SUCCESS, false, false],
			'no imagick with favicon and svg logo' => [false, false, true, 'image/svg+xml', SetupResult::INFO, false, true],
			'no imagick with svg logo' => [false, false, false, 'image/svg', SetupResult::INFO, true, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRun')]
	public function testRun(bool $canConvertSvg, bool $canConvertPng, bool $hasFavicon, string $logoMime, string $severity, bool $touchIconIssue, bool $logoIssue): void {
		$this->imageManager->method('canConvert')->willReturnMap([
			['SVG', $canConvertSvg],
			['PNG', $canConvertPng],
		]);
		$this->imageManager->method('hasImage')->with('favicon')->willReturn($hasFavicon);
		$this->imageManager->method('getImageMime')->with('logo')->willReturn($logoMime);

		$result = $this->check->run();

		$this->assertEquals($severity, $result->getSeverity());
		$description = $result->getDescription() ?? '';
		$this->assertEquals($touchIconIssue, str_contains($description, 'Add to Dock'));
		$this->assertEquals($logoIssue, str_contains($description, 'emails'));
	}
}
