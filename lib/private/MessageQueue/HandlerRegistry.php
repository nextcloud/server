<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\App\IAppManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\MessageQueue\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

/**
 * Maps message classes to their handler, as declared with #[AsMessageHandler]
 * on the classes listed in the <message-handlers> section of enabled apps.
 *
 * @psalm-type Handler = array{class: class-string, method: string}
 * @psalm-suppress ClassMustBeFinal For unit tests
 */
class HandlerRegistry {
	/** @var array<string, Handler>|null */
	private ?array $handlers = null;

	private readonly ICache $cache;

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly MessageMetadataReader $metadataReader,
		private readonly LoggerInterface $logger,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createLocal('message-queue');
	}

	/**
	 * @return Handler|null
	 */
	public function getHandler(string $messageClass): ?array {
		return $this->getHandlers()[$messageClass] ?? null;
	}

	/**
	 * @return array<string, Handler>
	 */
	public function getHandlers(): array {
		if ($this->handlers !== null) {
			return $this->handlers;
		}

		$apps = [];
		foreach ($this->appManager->getEnabledApps() as $app) {
			$apps[$app] = $this->appManager->getAppVersion($app);
		}

		$cacheKey = 'handlers-' . md5(json_encode($apps, JSON_THROW_ON_ERROR));

		/** @var array<string, Handler>|null $cached */
		$cached = $this->cache->get($cacheKey);
		if (is_array($cached)) {
			return $this->handlers = $cached;
		}

		$handlers = [];
		foreach (array_keys($apps) as $app) {
			$info = $this->appManager->getAppInfo($app);
			foreach ($info['message-handlers'] ?? [] as $handlerClass) {
				$this->collect($app, $handlerClass, $handlers);
			}
		}

		$this->cache->set($cacheKey, $handlers, 3600);
		return $this->handlers = $handlers;
	}

	/**
	 * @param array<string, Handler> $handlers
	 */
	private function collect(string $app, string $handlerClass, array &$handlers): void {
		if (!class_exists($handlerClass)) {
			$this->logger->error('Message handler ' . $handlerClass . ' of app ' . $app . ' does not exist');
			return;
		}

		$class = new \ReflectionClass($handlerClass);
		$methods = [];
		if ($class->getAttributes(AsMessageHandler::class) !== []) {
			if (!$class->hasMethod('__invoke')) {
				$this->logger->error('Message handler ' . $handlerClass . ' of app ' . $app . ' is marked with #[AsMessageHandler] but has no __invoke method');
				return;
			}

			$methods['__invoke'] = $class->getMethod('__invoke');
		}

		foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->getAttributes(AsMessageHandler::class) !== []) {
				$methods[$method->getName()] = $method;
			}
		}

		if ($methods === []) {
			$this->logger->error('Message handler ' . $handlerClass . ' of app ' . $app . ' has no #[AsMessageHandler] attribute');
			return;
		}

		foreach ($methods as $method) {
			$type = ($method->getParameters()[0] ?? null)?->getType();
			$messageClass = $type instanceof \ReflectionNamedType ? $type->getName() : null;
			if ($messageClass === null || !$this->metadataReader->isMessage($messageClass)) {
				$this->logger->error('First parameter of message handler ' . $handlerClass . '::' . $method->getName() . ' must be a class marked with #[AsMessage]');
				continue;
			}

			if (isset($handlers[$messageClass])) {
				$this->logger->error('Message ' . $messageClass . ' already has a handler, ignoring ' . $handlerClass . '::' . $method->getName());
				continue;
			}

			$handlers[$messageClass] = ['class' => $handlerClass, 'method' => $method->getName()];
		}
	}
}
