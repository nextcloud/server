<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db\Migrations;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OCP\App\IAppManager;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMetadataCommand extends Command {
	public function __construct(
		private readonly Connection $connection,
		private readonly IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this->setName('migrations:generate-metadata')
			->setHidden(true)
			->setDescription('Generate metadata from DB migrations - internal and should not be used');

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln(
			json_encode(
				[
					'migrations' => $this->extractMigrationMetadata()
				],
				JSON_PRETTY_PRINT
			)
		);

		return 0;
	}

	private function extractMigrationMetadata(): array {
		return [
			'core' => $this->extractMigrationMetadataFromCore(),
			'apps' => $this->extractMigrationMetadataFromApps()
		];
	}

	private function extractMigrationMetadataFromCore(): array {
		return $this->extractMigrationAttributes('core');
	}

	/**
	 * get all apps and extract attributes
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function extractMigrationMetadataFromApps(): array {
		$allApps = \OC_App::getAllApps();
		$metadata = [];
		foreach ($allApps as $appId) {
			// We need to load app before being able to extract Migrations
			// If app was not enabled before, we will disable it afterward.
			$alreadyLoaded = $this->appManager->isInstalled($appId);
			if (!$alreadyLoaded) {
				$this->appManager->loadApp($appId);
			}
			$metadata[$appId] = $this->extractMigrationAttributes($appId);
			if (!$alreadyLoaded) {
				$this->appManager->disableApp($appId);
			}
		}
		return $metadata;
	}

	/**
	 * We get all migrations from an app, and for each migration we extract attributes
	 *
	 * @param string $appId
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function extractMigrationAttributes(string $appId): array {
		$ms = new MigrationService($appId, $this->connection);

		$metadata = [];
		foreach($ms->getAvailableVersions() as $version) {
			$metadata[$version] = [];
			$class = new ReflectionClass($ms->createInstance($version));
			$attributes = $class->getAttributes();
			foreach ($attributes as $attribute) {
				$metadata[$version][] = $attribute->newInstance();
			}
		}

		return $metadata;
	}
}
