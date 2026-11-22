<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files;

use OC\Files\FilenameValidator;
use OCP\Capabilities\IPublicCapability;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PublicCapabilitiesTest extends TestCase {

	protected FilenameValidator&MockObject $filenameValidator;
	protected PublicCapabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();
		$this->filenameValidator = $this->createMock(FilenameValidator::class);
		$this->capabilities = new PublicCapabilities($this->filenameValidator);
	}

	public function testIsPublicCapability(): void {
		self::assertInstanceOf(IPublicCapability::class, $this->capabilities);
	}

	public function testGetCapabilities(): void {
		$this->filenameValidator->method('getForbiddenFilenames')->willReturn(['.htaccess']);
		$this->filenameValidator->method('getForbiddenBasenames')->willReturn(['con', 'prn']);
		$this->filenameValidator->method('getForbiddenCharacters')->willReturn(['/', ':']);
		$this->filenameValidator->method('getForbiddenExtensions')->willReturn(['.part']);

		self::assertEquals([
			'files' => [
				'$comment' => '"blacklisted_files" is deprecated as of Nextcloud 30, use "forbidden_filenames" instead',
				'blacklisted_files' => ['.htaccess'],
				'forbidden_filenames' => ['.htaccess'],
				'forbidden_filename_basenames' => ['con', 'prn'],
				'forbidden_filename_characters' => ['/', ':'],
				'forbidden_filename_extensions' => ['.part'],
			],
		], $this->capabilities->getCapabilities());
	}
}
