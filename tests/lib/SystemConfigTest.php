<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\Config;
use OC\SystemConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SystemConfigTest
 *
 * @package Test
 */
class SystemConfigTest extends TestCase {
	private Config&MockObject $config;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(Config::class);
	}

	public function testGetFilteredValueMasksTheEuroOfficeSecret(): void {
		$this->config->method('getValue')
			->willReturnMap([
				['config_extra_sensitive_values', [], []],
				['eurooffice', '', [
					'editors_check_interval' => 0,
					'jwt_secret' => 'a-document-server-signing-key',
					'jwt_header' => 'AuthorizationJwt',
				]],
			]);

		$systemConfig = new SystemConfig($this->config);

		$this->assertSame([
			'editors_check_interval' => 0,
			'jwt_secret' => IConfig::SENSITIVE_VALUE,
			'jwt_header' => 'AuthorizationJwt',
		], $systemConfig->getFilteredValue('eurooffice'));
	}

	public static function dataS3EncryptionSecrets(): array {
		return [
			'objectstore' => ['objectstore'],
			'objectstore_multibucket' => ['objectstore_multibucket'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataS3EncryptionSecrets')]
	public function testGetFilteredValueMasksS3EncryptionSecrets(string $key): void {
		$this->config->method('getValue')
			->willReturnMap([
				['config_extra_sensitive_values', [], []],
				[$key, '', [
					'class' => 'OC\\Files\\ObjectStore\\S3',
					'arguments' => [
						'bucket' => 'nextcloud',
						'sse' => 'sse-kms',
						'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
						'sse_c_key' => 'a-customer-provided-key',
						'sse_kms_encryption_context' => ['tenant' => 'acme'],
					],
				]],
			]);

		$systemConfig = new SystemConfig($this->config);

		$this->assertSame([
			'class' => 'OC\\Files\\ObjectStore\\S3',
			'arguments' => [
				'bucket' => 'nextcloud',
				'sse' => 'sse-kms',
				'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
				'sse_c_key' => IConfig::SENSITIVE_VALUE,
				'sse_kms_encryption_context' => IConfig::SENSITIVE_VALUE,
			],
		], $systemConfig->getFilteredValue($key));
	}
}
