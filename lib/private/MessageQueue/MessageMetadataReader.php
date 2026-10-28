<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\MessageQueue\Attribute\AsMessage;
use OCP\MessageQueue\Exception\InvalidMessageException;

final class MessageMetadataReader {
	/** @var array<string, AsMessage|null> */
	private array $cache = [];

	/**
	 * @psalm-assert-if-true class-string $class
	 */
	public function isMessage(string $class): bool {
		return $this->read($class) instanceof AsMessage;
	}

	/**
	 * @throws InvalidMessageException
	 */
	public function get(string $class): AsMessage {
		$metadata = $this->read($class);
		if (!$metadata instanceof AsMessage) {
			throw new InvalidMessageException('Class ' . $class . ' is not marked with #[AsMessage]');
		}

		return $metadata;
	}

	private function read(string $class): ?AsMessage {
		if (!array_key_exists($class, $this->cache)) {
			if (!class_exists($class)) {
				return null;
			}

			$attributes = (new \ReflectionClass($class))->getAttributes(AsMessage::class);
			$this->cache[$class] = $attributes === [] ? null : $attributes[0]->newInstance();
		}

		return $this->cache[$class];
	}
}
