<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Schema;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\FilesMetadata\Schema\IMetadataSchemaProvider;
use OCP\FilesMetadata\Schema\MetadataField;
use OCP\FilesMetadata\Schema\MetadataFieldOption;
use OCP\FilesMetadata\Schema\MetadataFieldType;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Loads the metadata fields registered by apps, shared by all schema managers
 */
class MetadataSchemaRegistry {
	/** @var array<string, MetadataField>|null */
	private ?array $fields = null;

	/** @var array<string, IMetadataSchemaProvider> providers indexed by the key of the fields they own */
	private array $providers = [];

	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return array<string, MetadataField> fields indexed by their key
	 */
	public function getFields(): array {
		if ($this->fields !== null) {
			return $this->fields;
		}

		$this->fields = [];
		$context = $this->coordinator->getRegistrationContext();
		foreach ($context?->getMetadataSchemaProviders() ?? [] as $registration) {
			$appId = $registration->getAppId();
			try {
				/** @var IMetadataSchemaProvider $provider */
				$provider = $this->container->get($registration->getService());
				$fields = $provider->getFields();
			} catch (Throwable $e) {
				$this->logger->error('Failed to load metadata schema provider ' . $registration->getService(), [
					'app' => $appId,
					'exception' => $e,
				]);
				continue;
			}

			foreach ($fields as $field) {
				$key = $field->getKey();
				if ($field->getApp() !== $appId) {
					$this->logger->error('Metadata field "' . $key . '" must be registered by app "' . $field->getApp() . '"', ['app' => $appId]);
					continue;
				}
				if (!$this->isValidKey($key)) {
					$this->logger->error('Metadata field "' . $key . '" must only contain alphanumerical characters, dashes and underscores', ['app' => $appId]);
					continue;
				}
				if (isset($this->fields[$key])) {
					$this->logger->error('Metadata field "' . $key . '" is already registered', ['app' => $appId]);
					continue;
				}
				if (!$this->hasValidOptions($field)) {
					$this->logger->error('Metadata field "' . $key . '" must define unique options if and only if it is of type ' . MetadataFieldType::ENUM->value, ['app' => $appId]);
					continue;
				}

				$this->fields[$key] = $field;
				$this->providers[$key] = $provider;
			}
		}

		return $this->fields;
	}

	public function getProvider(string $key): ?IMetadataSchemaProvider {
		$this->getFields();
		return $this->providers[$key] ?? null;
	}

	private function isValidKey(string $key): bool {
		return ctype_alnum(str_replace(['-', '_'], '', $key));
	}

	private function hasValidOptions(MetadataField $field): bool {
		$values = array_map(static fn (MetadataFieldOption $option): string => $option->getValue(), $field->getOptions() ?? []);
		if ($field->getType() !== MetadataFieldType::ENUM) {
			return $values === [];
		}

		return $values !== [] && count(array_unique($values)) === count($values);
	}
}
