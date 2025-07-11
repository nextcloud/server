<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Migration;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OC\Migration\Exceptions\AttributeException;
use OCP\App\IAppManager;
use OCP\Migration\Attributes\GenericMigrationAttribute;
use OCP\Migration\Attributes\MigrationAttribute;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Helps managing DB Migrations' Metadata
 *
 * @since 30.0.0
 */
class MetadataManager {
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly Connection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * We get all migrations from an app (or 'core'), and
	 * for each migration files we extract its attributes
	 *
	 * @param string $appId
	 *
	 * @return array<string, MigrationAttribute[]>
	 * @since 30.0.0
	 */
	public function extractMigrationAttributes(string $appId): array {
		$ms = new MigrationService($appId, $this->connection);

		$metadata = [];
		foreach ($ms->getAvailableVersions() as $version) {
			$metadata[$version] = [];
			$class = new ReflectionClass($ms->createInstance($version));
			$attributes = $class->getAttributes();
			foreach ($attributes as $attribute) {
				$item = $attribute->newInstance();
				if ($item instanceof MigrationAttribute) {
					$metadata[$version][] = $item;
				}
			}
		}

		return $metadata;
	}

	/**
	 * convert direct data from release metadata into a list of Migrations' Attribute
	 *
	 * @param array<array-key, array<array-key, array>> $metadata
	 * @param bool $filterKnownMigrations ignore metadata already done in local instance
	 *
	 * @return array{apps: array<array-key, array<string, MigrationAttribute[]>>, core: array<string, MigrationAttribute[]>}
	 * @since 30.0.0
	 */
	public function getMigrationsAttributesFromReleaseMetadata(
		array $metadata,
		bool $filterKnownMigrations = false,
	): array {
		$appsAttributes = [];
		foreach (array_keys($metadata['apps']) as $appId) {
			if ($filterKnownMigrations && !$this->appManager->isEnabledForAnyone($appId)) {
				continue; // if not interested and app is not installed
			}

			$done = ($filterKnownMigrations) ? $this->getKnownMigrations($appId) : [];
			$appsAttributes[$appId] = $this->parseMigrations($metadata['apps'][$appId] ?? [], $done);
		}

		$done = ($filterKnownMigrations) ? $this->getKnownMigrations('core') : [];
		return [
			'core' => $this->parseMigrations($metadata['core'] ?? [], $done),
			'apps' => $appsAttributes
		];
	}

	/**
	 * returns list of installed apps that does not support migrations metadata (yet)
	 *
	 * @param array<array-key, array<array-key, array>> $metadata
	 *
	 * @return string[]
	 * @since 30.0.0
	 */
	public function getUnsupportedApps(array $metadata): array {
		return array_values(array_diff($this->appManager->getEnabledApps(), array_keys($metadata['apps'])));
	}

	/**
	 * convert raw data to a list of MigrationAttribute
	 *
	 * @param array $migrations
	 * @param array $ignoreMigrations
	 *
	 * @return array<string, MigrationAttribute[]>
	 */
	private function parseMigrations(array $migrations, array $ignoreMigrations = []): array {
		$parsed = [];
		foreach (array_keys($migrations) as $entry) {
			if (in_array($entry, $ignoreMigrations)) {
				continue;
			}

			$parsed[$entry] = [];
			foreach ($migrations[$entry] as $item) {
				try {
					$parsed[$entry][] = $this->createAttribute($item);
				} catch (AttributeException $e) {
					$this->logger->warning('exception while trying to create attribute', ['exception' => $e, 'item' => json_encode($item)]);
					$parsed[$entry][] = new GenericMigrationAttribute($item);
				}
			}
		}

		return $parsed;
	}

	/**
	 * returns migrations already done
	 *
	 * @param string $appId
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getKnownMigrations(string $appId): array {
		$ms = new MigrationService($appId, $this->connection);
		return $ms->getMigratedVersions();
	}

	/**
	 * generate (deserialize) a MigrationAttribute from a serialized version
	 *
	 * @param array $item
	 *
	 * @return MigrationAttribute
	 * @throws AttributeException
	 */
	private function createAttribute(array $item): MigrationAttribute {
		$class = $item['class'] ?? '';
		$namespace = 'OCP\Migration\Attributes\\';
		if (!str_starts_with($class, $namespace)
			|| !ctype_alpha(substr($class, strlen($namespace)))) {
			throw new AttributeException('class name does not looks valid');
		}

		try {
			$attribute = new $class($item['table'] ?? '');
			return $attribute->import($item);
		} catch (\Error) {
			throw new AttributeException('cannot import Attribute');
		}
	}
}
