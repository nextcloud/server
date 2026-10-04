<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\MessageQueue\Exception\InvalidMessageException;
use OCP\MessageQueue\IMessageBus;

final readonly class MessageBus implements IMessageBus {
	public function __construct(
		private MessageStore $store,
		private HandlerRegistry $handlerRegistry,
		private MessageMetadataReader $metadataReader,
		private MessageNormalizer $normalizer,
		private AfterRequestHandler $afterRequestHandler,
	) {
	}

	#[\Override]
	public function dispatch(object $message): void {
		$class = $message::class;
		if ($this->handlerRegistry->getHandler($class) === null) {
			throw new InvalidMessageException('No handler registered for message ' . $class);
		}

		$metadata = $this->metadataReader->get($class);
		$data = $this->normalizer->normalize($message);

		$deduplicationHash = null;
		if ($metadata->deduplicateBy !== []) {
			$key = array_intersect_key($data, array_flip($metadata->deduplicateBy));
			ksort($key);
			$deduplicationHash = hash('sha256', $class . json_encode($key, JSON_THROW_ON_ERROR));
		}

		$id = $this->store->add($metadata->queue, $class, json_encode($data, JSON_THROW_ON_ERROR), $deduplicationHash);
		if ($id !== null) {
			$this->afterRequestHandler->add($metadata->queue, $id);
		}
	}
}
