<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\NodeAttributeProviderRegistration;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\Files\NodeAttributes\NodeAttributeRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class NodeAttributeRegistryTest extends TestCase {
	private const HAS_PREVIEW = '{http://nextcloud.org/ns}has-preview';
	private const FAVORITE = '{http://owncloud.org/ns}favorite';
	private const TAGS = '{http://owncloud.org/ns}tags';

	private Coordinator&MockObject $coordinator;
	private RegistrationContext&MockObject $context;
	private ContainerInterface&MockObject $container;
	private LoggerInterface&MockObject $logger;
	private NodeAttributeRegistry $registry;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->context = $this->createMock(RegistrationContext::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->coordinator->method('getRegistrationContext')->willReturn($this->context);

		$this->registry = new NodeAttributeRegistry($this->coordinator, $this->container, $this->logger);
	}

	/**
	 * @param array<class-string, list<string>> $providers attributes by provider class
	 */
	private function register(array $providers): void {
		$this->context->method('getNodeAttributeProviders')->willReturn(array_map(
			static fn (string $class, array $attributes) => new NodeAttributeProviderRegistration('app', $class, $attributes),
			array_keys($providers),
			array_values($providers),
		));
	}

	public function testGetAttributeNames(): void {
		$this->register(['PreviewProvider' => [self::HAS_PREVIEW], 'TagsProvider' => [self::FAVORITE, self::TAGS]]);

		self::assertSame([self::HAS_PREVIEW, self::FAVORITE, self::TAGS], $this->registry->getAttributeNames());
	}

	public function testGetProvidersForGroupsAttributesByProvider(): void {
		$this->register(['PreviewProvider' => [self::HAS_PREVIEW], 'TagsProvider' => [self::FAVORITE, self::TAGS]]);
		$previewProvider = $this->createMock(INodeAttributeProvider::class);
		$tagsProvider = $this->createMock(INodeAttributeProvider::class);
		$this->container->expects(self::exactly(2))
			->method('get')
			->willReturnMap([['PreviewProvider', $previewProvider], ['TagsProvider', $tagsProvider]]);

		$groups = $this->registry->getProvidersFor([self::FAVORITE, '{DAV:}unknown', self::HAS_PREVIEW, self::TAGS, self::FAVORITE]);

		self::assertSame([
			['provider' => $tagsProvider, 'attributes' => [self::FAVORITE, self::TAGS]],
			['provider' => $previewProvider, 'attributes' => [self::HAS_PREVIEW]],
		], $groups);

		// Providers are only loaded once
		$this->registry->getProvidersFor([self::HAS_PREVIEW, self::TAGS]);
	}

	public function testAttributesMustHaveUniqueLocalNames(): void {
		$this->register(['FirstProvider' => [self::FAVORITE], 'OtherProvider' => ['{http://example.com/ns}favorite']]);
		$this->logger->expects(self::once())->method('error');

		self::assertSame([self::FAVORITE], $this->registry->getAttributeNames());
	}

	public function testBrokenProvidersAreSkipped(): void {
		$this->register(['BrokenProvider' => [self::HAS_PREVIEW], 'NotAProvider' => [self::FAVORITE]]);
		$this->container->method('get')->willReturnCallback(static fn (string $class) => match ($class) {
			'BrokenProvider' => throw new \RuntimeException('broken'),
			default => new \stdClass(),
		});
		$this->logger->expects(self::exactly(2))->method('error');

		self::assertSame([], $this->registry->getProvidersFor([self::HAS_PREVIEW, self::FAVORITE]));
	}

	public function testNothingRegisteredBeforeAppsAreLoaded(): void {
		$coordinator = $this->createMock(Coordinator::class);
		$coordinator->method('getRegistrationContext')->willReturnOnConsecutiveCalls(null, $this->context);
		$this->register(['PreviewProvider' => [self::HAS_PREVIEW]]);
		$registry = new NodeAttributeRegistry($coordinator, $this->container, $this->logger);

		self::assertSame([], $registry->getAttributeNames());
		self::assertSame([self::HAS_PREVIEW], $registry->getAttributeNames());
	}

	public function testGetLocalName(): void {
		self::assertSame('has-preview', NodeAttributeRegistry::getLocalName(self::HAS_PREVIEW));
		self::assertSame('plain', NodeAttributeRegistry::getLocalName('plain'));
	}
}
