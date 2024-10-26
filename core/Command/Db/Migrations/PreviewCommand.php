<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db\Migrations;

use OC\Migration\MetadataManager;
use OC\Updater\ReleaseMetadata;
use OCP\Migration\Attributes\MigrationAttribute;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 30.0.0
 */
class PreviewCommand extends Command {
	private bool $initiated = false;
	public function __construct(
		private readonly MetadataManager $metadataManager,
		private readonly ReleaseMetadata $releaseMetadata,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('migrations:preview')
			->setDescription('Get preview of available DB migrations in case of initiating an upgrade')
			->addArgument('version', InputArgument::REQUIRED, 'The destination version number');

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$version = $input->getArgument('version');
		if (filter_var($version, FILTER_VALIDATE_URL)) {
			$metadata = $this->releaseMetadata->downloadMetadata($version);
		} elseif (str_starts_with($version, '/')) {
			$metadata = json_decode(file_get_contents($version), true, flags: JSON_THROW_ON_ERROR);
		} else {
			$metadata = $this->releaseMetadata->getMetadata($version);
		}

		$parsed = $this->metadataManager->getMigrationsAttributesFromReleaseMetadata($metadata['migrations'] ?? [], true);

		$table = new Table($output);
		$this->displayMigrations($table, 'core', $parsed['core'] ?? []);
		foreach ($parsed['apps'] as $appId => $migrations) {
			if (!empty($migrations)) {
				$this->displayMigrations($table, $appId, $migrations);
			}
		}
		$table->render();

		$unsupportedApps = $this->metadataManager->getUnsupportedApps($metadata['migrations']);
		if (!empty($unsupportedApps)) {
			$output->writeln('');
			$output->writeln('Those apps are not supporting metadata yet and might initiate migrations on upgrade: <info>' . implode(', ', $unsupportedApps) . '</info>');
		}

		return 0;
	}

	private function displayMigrations(Table $table, string $appId, array $data): void {
		if (empty($data)) {
			return;
		}

		if ($this->initiated) {
			$table->addRow(new TableSeparator());
		}
		$this->initiated = true;

		$table->addRow(
			[
				new TableCell(
					$appId,
					[
						'colspan' => 2,
						'style' => new TableCellStyle(['cellFormat' => '<info>%s</info>'])
					]
				)
			]
		)->addRow(new TableSeparator());

		/** @var MigrationAttribute[] $attributes */
		foreach ($data as $migration => $attributes) {
			$attributesStr = [];
			if (empty($attributes)) {
				$attributesStr[] = '<comment>(metadata not set)</comment>';
			}
			foreach ($attributes as $attribute) {
				$definition = '<info>' . $attribute->definition() . '</info>';
				$definition .= empty($attribute->getDescription()) ? '' : "\n  " . $attribute->getDescription();
				$definition .= empty($attribute->getNotes()) ? '' : "\n  <comment>" . implode("</comment>\n  <comment>", $attribute->getNotes()) . '</comment>';
				$attributesStr[] = $definition;
			}
			$table->addRow([$migration, implode("\n", $attributesStr)]);
		}
	}
}
