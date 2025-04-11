<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db\Migrations;

use OC\Migration\MetadataManager;
use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 30.0.0
 */
class GenerateMetadataCommand extends Command {
	public function __construct(
		private readonly MetadataManager $metadataManager,
		private readonly IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
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
		return $this->metadataManager->extractMigrationAttributes('core');
	}

	/**
	 * get all apps and extract attributes
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function extractMigrationMetadataFromApps(): array {
		$allApps = $this->appManager->getAllAppsInAppsFolders();
		$metadata = [];
		foreach ($allApps as $appId) {
			// We need to load app before being able to extract Migrations
			$alreadyLoaded = $this->appManager->isAppLoaded($appId);
			if (!$alreadyLoaded) {
				$this->appManager->loadApp($appId);
			}
			$metadata[$appId] = $this->metadataManager->extractMigrationAttributes($appId);
		}
		return $metadata;
	}
}
