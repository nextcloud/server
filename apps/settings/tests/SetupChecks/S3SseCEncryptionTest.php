<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\SetupChecks;

use OCA\Settings\SetupChecks\S3SseCEncryption;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class S3SseCEncryptionTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnCallback(function ($message, array $replace = []) {
				return vsprintf($message, $replace);
			});
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
	}

	public static function dataRun(): array {
		$s3 = static fn (array $arguments): array => ['class' => 'OC\\Files\\ObjectStore\\S3', 'arguments' => $arguments];

		return [
			'no objectstore configured' => [null, null, SetupResult::SUCCESS],
			'non-S3 objectstore class' => [['class' => 'OC\\Files\\ObjectStore\\Swift', 'arguments' => ['sse_c_key' => 'x']], null, SetupResult::SUCCESS],
			'S3 with no sse* arguments' => [$s3([]), null, SetupResult::SUCCESS],
			'S3 with sse-kms' => [$s3(['sse' => 'sse-kms']), null, SetupResult::SUCCESS],
			'S3 with sse-kms-dsse' => [$s3(['sse' => 'sse-kms-dsse']), null, SetupResult::SUCCESS],
			'S3 with legacy sse_c_key only' => [$s3(['sse_c_key' => 'x']), null, SetupResult::WARNING],
			'S3 with explicit sse => sse-c' => [$s3(['sse' => 'sse-c', 'sse_c_key' => 'x']), null, SetupResult::WARNING],
			'S3 with both legacy keys set (sse_c_key wins by precedence)' => [$s3(['sse_c_key' => 'x', 'sse_kms_enabled' => true]), null, SetupResult::WARNING],
			'S3 with explicit sse overriding legacy sse_c_key' => [$s3(['sse' => 'sse-kms', 'sse_c_key' => 'x']), null, SetupResult::SUCCESS],
			'multibucket S3 with legacy sse_c_key only' => [null, $s3(['sse_c_key' => 'x']), SetupResult::WARNING],
			'multibucket S3 with sse-kms' => [null, $s3(['sse' => 'sse-kms']), SetupResult::SUCCESS],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRun')]
	public function testRun(?array $objectStore, ?array $objectStoreMultibucket, string $expected): void {
		$this->urlGenerator->method('linkToDocs')->willReturn('admin-s3-sse-c');

		$this->config->method('getSystemValue')
			->willReturnCallback(function (string $key, $default = null) use ($objectStore, $objectStoreMultibucket) {
				return match ($key) {
					'objectstore' => $objectStore ?? $default,
					'objectstore_multibucket' => $objectStoreMultibucket ?? $default,
					default => $default,
				};
			});

		$check = new S3SseCEncryption($this->l10n, $this->config, $this->urlGenerator);

		$result = $check->run();
		$this->assertEquals($expected, $result->getSeverity());
	}
}
