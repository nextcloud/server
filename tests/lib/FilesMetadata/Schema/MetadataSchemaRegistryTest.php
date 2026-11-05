<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\FilesMetadata\Schema;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\FilesMetadata\Schema\MetadataSchemaRegistry;
use OCP\FilesMetadata\Schema\IMetadataSchemaProvider;
use OCP\FilesMetadata\Schema\MetadataField;
use OCP\FilesMetadata\Schema\MetadataFieldOption;
use OCP\FilesMetadata\Schema\MetadataFieldType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;

class MetadataSchemaRegistryTest extends TestCase {
	private RegistrationContext&MockObject $context;
	private LoggerInterface&MockObject $logger;
	private MetadataSchemaRegistry $registry;

	/** @var array<class-string, IMetadataSchemaProvider> */
	private array $providers = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->context = $this->createMock(RegistrationContext::class);
		$coordinator = $this->createMock(Coordinator::class);
		$coordinator->method('getRegistrationContext')->willReturn($this->context);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')
			->willReturnCallback(fn (string $class) => $this->providers[$class] ?? throw new RuntimeException('not found'));
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->registry = new MetadataSchemaRegistry($coordinator, $container, $this->logger);
	}

	/**
	 * @param array<string, array{string, IMetadataSchemaProvider|null}> $registrations app id and provider indexed by class name
	 */
	private function register(array $registrations): void {
		$serviceRegistrations = [];
		foreach ($registrations as $class => [$appId, $provider]) {
			if ($provider !== null) {
				$this->providers[$class] = $provider;
			}
			$serviceRegistrations[] = new ServiceRegistration($appId, $class);
		}
		$this->context->method('getMetadataSchemaProviders')->willReturn($serviceRegistrations);
	}

	private function createProvider(MetadataField ...$fields): IMetadataSchemaProvider&MockObject {
		$provider = $this->createMock(IMetadataSchemaProvider::class);
		$provider->method('getFields')->willReturn($fields);
		return $provider;
	}

	public function testGetFields(): void {
		$label = new MetadataField('files', 'label', MetadataFieldType::STRING, 'Sensitivity label');
		$hash = new MetadataField('hasher', 'content_hash', MetadataFieldType::STRING, 'Content hash');
		$filesProvider = $this->createProvider($label);
		$hasherProvider = $this->createProvider($hash);
		$this->register([
			'FilesProvider' => ['files', $filesProvider],
			'HasherProvider' => ['hasher', $hasherProvider],
		]);

		$this->assertSame([
			'label' => $label,
			'content_hash' => $hash,
		], $this->registry->getFields());
		$this->assertSame($filesProvider, $this->registry->getProvider('label'));
		$this->assertSame($hasherProvider, $this->registry->getProvider('content_hash'));
		$this->assertNull($this->registry->getProvider('unknown'));
	}

	public function testGetFieldsLoadsProvidersOnce(): void {
		$provider = $this->createMock(IMetadataSchemaProvider::class);
		$provider->expects($this->once())
			->method('getFields')
			->willReturn([new MetadataField('app', 'field', MetadataFieldType::BOOL, 'Field')]);
		$this->register(['Provider' => ['app', $provider]]);

		$this->registry->getFields();
		$this->registry->getProvider('field');
	}

	public static function dataInvalidKey(): array {
		return [
			'empty' => [''],
			'invalid characters' => ['field.name'],
			'whitespace' => ['field name'],
		];
	}

	#[DataProvider('dataInvalidKey')]
	public function testGetFieldsSkipsInvalidKey(string $key): void {
		$this->register([
			'Provider' => ['app', $this->createProvider(new MetadataField('app', $key, MetadataFieldType::STRING, 'Field'))],
		]);
		$this->logger->expects($this->once())->method('error');

		$this->assertSame([], $this->registry->getFields());
	}

	public function testGetFieldsSkipsFieldOfOtherApp(): void {
		$this->register([
			'Provider' => ['app', $this->createProvider(new MetadataField('files', 'label', MetadataFieldType::STRING, 'Label'))],
		]);
		$this->logger->expects($this->once())->method('error');

		$this->assertSame([], $this->registry->getFields());
	}

	public function testGetFieldsSkipsDuplicateKey(): void {
		$first = new MetadataField('app', 'field', MetadataFieldType::STRING, 'First');
		$second = new MetadataField('other', 'field', MetadataFieldType::INT, 'Second');
		$firstProvider = $this->createProvider($first);
		$this->register([
			'FirstProvider' => ['app', $firstProvider],
			'SecondProvider' => ['other', $this->createProvider($second)],
		]);
		$this->logger->expects($this->once())->method('error');

		$this->assertSame(['field' => $first], $this->registry->getFields());
		$this->assertSame($firstProvider, $this->registry->getProvider('field'));
	}

	public function testGetFieldsSkipsFailingProvider(): void {
		$field = new MetadataField('app', 'field', MetadataFieldType::STRING, 'Field');
		$this->register([
			'MissingProvider' => ['broken', null],
			'Provider' => ['app', $this->createProvider($field)],
		]);
		$this->logger->expects($this->once())->method('error');

		$this->assertSame(['field' => $field], $this->registry->getFields());
	}

	public static function dataInvalidOptions(): array {
		return [
			'enum without options' => [MetadataFieldType::ENUM, null],
			'enum with empty options' => [MetadataFieldType::ENUM, []],
			'enum with duplicate options' => [MetadataFieldType::ENUM, [
				new MetadataFieldOption('public', 'Public'),
				new MetadataFieldOption('public', 'Also public'),
			]],
			'options on string field' => [MetadataFieldType::STRING, [new MetadataFieldOption('public', 'Public')]],
		];
	}

	#[DataProvider('dataInvalidOptions')]
	public function testGetFieldsSkipsInvalidOptions(MetadataFieldType $type, ?array $options): void {
		$this->register([
			'Provider' => ['app', $this->createProvider(new MetadataField('app', 'field', $type, 'Field', '', $options))],
		]);
		$this->logger->expects($this->once())->method('error');

		$this->assertSame([], $this->registry->getFields());
	}
}
