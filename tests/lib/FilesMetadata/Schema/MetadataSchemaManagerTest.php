<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\FilesMetadata\Schema;

use DateTimeImmutable;
use OC\FilesMetadata\Schema\MetadataSchemaManager;
use OC\FilesMetadata\Schema\MetadataSchemaRegistry;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataValidationException;
use OCP\FilesMetadata\Schema\IMetadataSchemaProvider;
use OCP\FilesMetadata\Schema\MetadataField;
use OCP\FilesMetadata\Schema\MetadataFieldOption;
use OCP\FilesMetadata\Schema\MetadataFieldType;
use OCP\IUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;

class MetadataSchemaManagerTest extends TestCase {
	private MetadataSchemaRegistry&MockObject $registry;
	private LoggerInterface&MockObject $logger;
	private MetadataSchemaManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(MetadataSchemaRegistry::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new MetadataSchemaManager($this->registry, $this->logger);
	}

	private function registerField(MetadataField $field): IMetadataSchemaProvider&MockObject {
		$provider = $this->createMock(IMetadataSchemaProvider::class);
		$this->registry->method('getFields')->willReturn([$field->getKey() => $field]);
		$this->registry->method('getProvider')->willReturnMap([[$field->getKey(), $provider]]);
		return $provider;
	}

	private static function createOptions(): array {
		return [
			new MetadataFieldOption('public', 'Public'),
			new MetadataFieldOption('confidential', 'Confidential'),
		];
	}

	private static function createField(MetadataFieldType $type): MetadataField {
		return new MetadataField('app', 'field', $type, 'Field', '', $type === MetadataFieldType::ENUM ? self::createOptions() : null);
	}

	public function testGetFields(): void {
		$field = self::createField(MetadataFieldType::STRING);
		$this->registerField($field);

		$this->assertSame(['field' => $field], $this->manager->getFields());
	}

	public static function dataValidValue(): array {
		return [
			'string' => [MetadataFieldType::STRING, 'confidential', 'confidential'],
			'empty string' => [MetadataFieldType::STRING, '', ''],
			'int' => [MetadataFieldType::INT, 42, 42],
			'int from string' => [MetadataFieldType::INT, '-42', -42],
			'float' => [MetadataFieldType::FLOAT, 4.2, 4.2],
			'float from int' => [MetadataFieldType::FLOAT, 4, 4.0],
			'float from string' => [MetadataFieldType::FLOAT, '4.2', 4.2],
			'bool' => [MetadataFieldType::BOOL, true, true],
			'bool from string' => [MetadataFieldType::BOOL, 'true', true],
			'bool from numeric string' => [MetadataFieldType::BOOL, '0', false],
			'date from timestamp' => [MetadataFieldType::DATE, 1767225600, 1767225600],
			'date from DateTime' => [MetadataFieldType::DATE, new DateTimeImmutable('2026-01-01T00:00:00+00:00'), 1767225600],
			'date from ISO 8601' => [MetadataFieldType::DATE, '2026-01-01T01:00:00+01:00', 1767225600],
			'enum' => [MetadataFieldType::ENUM, 'confidential', 'confidential'],
		];
	}

	#[DataProvider('dataValidValue')]
	public function testValidateValue(MetadataFieldType $type, mixed $value, string|int|float|bool $expected): void {
		$field = self::createField($type);
		$provider = $this->registerField($field);
		$provider->expects($this->once())->method('validateValue')->with($field, $expected);

		$this->assertSame($expected, $this->manager->validateValue('field', $value));
	}

	public function testValidateValueDateOnly(): void {
		$this->registerField(self::createField(MetadataFieldType::DATE));

		$this->assertSame(
			(new DateTimeImmutable('2026-01-01 00:00:00'))->getTimestamp(),
			$this->manager->validateValue('field', '2026-01-01'),
		);
	}

	public static function dataInvalidValue(): array {
		return [
			'string from int' => [MetadataFieldType::STRING, 42],
			'string from null' => [MetadataFieldType::STRING, null],
			'int from float' => [MetadataFieldType::INT, 4.2],
			'int from decimal string' => [MetadataFieldType::INT, '4.2'],
			'int from text' => [MetadataFieldType::INT, 'abc'],
			'float from text' => [MetadataFieldType::FLOAT, 'abc'],
			'float from infinity' => [MetadataFieldType::FLOAT, INF],
			'float from NaN' => [MetadataFieldType::FLOAT, NAN],
			'float from bool' => [MetadataFieldType::FLOAT, true],
			'bool from text' => [MetadataFieldType::BOOL, 'yes'],
			'bool from int' => [MetadataFieldType::BOOL, 1],
			'date from text' => [MetadataFieldType::DATE, 'tomorrow'],
			'date out of range' => [MetadataFieldType::DATE, '2026-13-01'],
			'date from float' => [MetadataFieldType::DATE, 4.2],
			'enum unknown option' => [MetadataFieldType::ENUM, 'secret'],
			'enum option label' => [MetadataFieldType::ENUM, 'Confidential'],
		];
	}

	#[DataProvider('dataInvalidValue')]
	public function testValidateValueInvalidType(MetadataFieldType $type, mixed $value): void {
		$provider = $this->registerField(self::createField($type));
		$provider->expects($this->never())->method('validateValue');

		$this->expectException(FilesMetadataValidationException::class);
		$this->manager->validateValue('field', $value);
	}

	public function testValidateValueRejectedByProvider(): void {
		$provider = $this->registerField(self::createField(MetadataFieldType::STRING));
		$exception = new FilesMetadataValidationException('must be a SHA-256 hash');
		$provider->method('validateValue')->willThrowException($exception);
		$this->logger->expects($this->never())->method('error');

		$this->expectExceptionObject($exception);
		$this->manager->validateValue('field', 'abc');
	}

	public function testValidateValueProviderFails(): void {
		$provider = $this->registerField(self::createField(MetadataFieldType::STRING));
		$provider->method('validateValue')->willThrowException(new RuntimeException('failure'));
		$this->logger->expects($this->once())->method('error');

		$this->expectException(FilesMetadataValidationException::class);
		$this->manager->validateValue('field', 'abc');
	}

	public function testValidateValueUnknownField(): void {
		$this->registry->method('getFields')->willReturn([]);

		$this->expectException(FilesMetadataNotFoundException::class);
		$this->manager->validateValue('unknown', 42);
	}

	public function testAccessIsDelegatedToOwningProvider(): void {
		$field = self::createField(MetadataFieldType::STRING);
		$provider = $this->registerField($field);
		$node = $this->createMock(Node::class);
		$user = $this->createMock(IUser::class);

		$provider->expects($this->once())->method('canRead')->with($field, $node, $user, null)->willReturn(true);
		$provider->expects($this->once())->method('canEdit')->with($field, $node, $user, null)->willReturn(false);

		$this->assertTrue($this->manager->canRead('field', $node, $user));
		$this->assertFalse($this->manager->canEdit('field', $node, $user));
	}

	public function testAccessPassesApp(): void {
		$field = self::createField(MetadataFieldType::STRING);
		$provider = $this->registerField($field);
		$manager = new MetadataSchemaManager($this->registry, $this->logger, 'governance');
		$node = $this->createMock(Node::class);

		$provider->expects($this->once())->method('canRead')->with($field, $node, null, 'governance')->willReturn(true);
		$provider->expects($this->once())->method('canEdit')->with($field, $node, null, 'governance')->willReturn(true);

		$this->assertTrue($manager->canRead('field', $node, null));
		$this->assertTrue($manager->canEdit('field', $node, null));
	}

	public function testAccessToUnknownFieldIsDenied(): void {
		$this->registry->method('getFields')->willReturn([]);
		$node = $this->createMock(Node::class);

		$this->assertFalse($this->manager->canRead('unknown', $node, null));
		$this->assertFalse($this->manager->canEdit('unknown', $node, null));
	}

	public function testAccessIsDeniedWhenProviderFails(): void {
		$provider = $this->registerField(self::createField(MetadataFieldType::STRING));
		$provider->method('canRead')->willThrowException(new RuntimeException('failure'));
		$provider->method('canEdit')->willThrowException(new RuntimeException('failure'));
		$this->logger->expects($this->exactly(2))->method('error');
		$node = $this->createMock(Node::class);

		$this->assertFalse($this->manager->canRead('field', $node, null));
		$this->assertFalse($this->manager->canEdit('field', $node, null));
	}

	public function testMetadataFieldSerialization(): void {
		$field = new MetadataField('app', 'field', MetadataFieldType::BOOL, 'Reviewed', 'Whether the file was reviewed');

		$this->assertSame([
			'key' => 'field',
			'type' => 'bool',
			'label' => 'Reviewed',
			'description' => 'Whether the file was reviewed',
			'options' => null,
		], $field->jsonSerialize());
	}

	public function testEnumFieldSerialization(): void {
		$field = new MetadataField('app', 'label', MetadataFieldType::ENUM, 'Sensitivity', '', self::createOptions());

		$this->assertSame(
			'{"key":"label","type":"enum","label":"Sensitivity","description":"","options":[{"value":"public","label":"Public"},{"value":"confidential","label":"Confidential"}]}',
			json_encode($field),
		);
	}
}
