<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Db\Migrations;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OCP\Migration\Attributes\GenericMigrationAttribute;
use OCP\Migration\Attributes\MigrationAttribute;
use OCP\Migration\Exceptions\AttributeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreviewCommand extends Command {
	public function __construct(
		private readonly Connection $connection,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('migrations:preview')
			->setDescription('Get preview of available DB migrations in case of initiating an upgrade')
			->addArgument('version', InputArgument::REQUIRED, 'The destination version number');

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$version = $input->getArgument('version');

		$metadata = $this->getMetadata($version);
		$parsed = $this->getMigrationsAttributes($metadata);

		$table = new Table($output);
		$this->displayMigrations($table, 'core', $parsed['core']);

		$table->render();

		return 0;
	}

	private function displayMigrations(Table $table, string $appId, array $data): void {
		$done = $this->getDoneMigrations($appId);
		$done = array_diff($done, ['30000Date20240429122720']);

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

		foreach($data as $migration => $attributes) {
			if (in_array($migration, $done)) {
				continue;
			}

			$attributesStr = [];
			/** @var MigrationAttribute[] $attributes */
			foreach($attributes as $attribute) {
				$definition = '<info>' . $attribute->definition() . "</info>";
				$definition .= empty($attribute->getDescription()) ? '' : "\n  " . $attribute->getDescription();
				$definition .= empty($attribute->getNotes()) ? '' : "\n  <comment>" . implode("</comment>\n  <comment>", $attribute->getNotes()) . '</comment>';
				$attributesStr[] = $definition;
			}
			$table->addRow([$migration, implode("\n", $attributesStr)]);
		}

	}





	private function getMetadata(string $version): array {
		$metadata = json_decode(file_get_contents('/tmp/nextcloud-' . $version . '.metadata'), true);
		if (!$metadata) {
			throw new \Exception();
		}
		return $metadata['migrations'] ?? [];
	}

	private function getDoneMigrations(string $appId): array {
		$ms = new MigrationService($appId, $this->connection);
		return $ms->getMigratedVersions();
	}

	private function getMigrationsAttributes(array $metadata): array {
		$appsAttributes = [];
		foreach (array_keys($metadata['apps']) as $appId) {
			$appsAttributes[$appId] = $this->parseMigrations($metadata['apps'][$appId] ?? []);
		}

		return [
			'core' => $this->parseMigrations($metadata['core'] ?? []),
			'apps' => $appsAttributes
		];
	}

	private function parseMigrations(array $migrations): array {
		$parsed = [];
		foreach (array_keys($migrations) as $entry) {
			$items = $migrations[$entry];
			$parsed[$entry] = [];
			foreach ($items as $item) {
 				try {
					$parsed[$entry][] = $this->createAttribute($item);
				} catch (AttributeException $e) {
					$this->logger->warning(
						'exception while trying to create attribute',
						['exception' => $e, 'item' => json_encode($item)]
					);
					$parsed[$entry][] = new GenericMigrationAttribute($item);
				}
			}
		}

		return $parsed;
	}

	/**
	 * @param array $item
	 *
	 * @return MigrationAttribute|null
	 * @throws AttributeException
	 */
	private function createAttribute(array $item): ?MigrationAttribute {
		$class = $item['class'] ?? '';
		$namespace = 'OCP\Migration\Attributes\\';
		if (!str_starts_with($class, $namespace)
			|| !ctype_alpha(substr($class, strlen($namespace)))) {
			throw new AttributeException('class name does not looks valid');
		}

		try {
			$attribute = new $class();
			return $attribute->import($item);
		} catch (\Error) {
			throw new AttributeException('cannot import Attribute');
		}
	}
}
