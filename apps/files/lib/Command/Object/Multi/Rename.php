<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object\Multi;

use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\IQuestionHelper;
use OCP\IConfig;
use OCP\IDBConnection;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
	name: 'files:object:multi:rename-config',
	description: 'Rename an object store configuration and move all users over to the new configuration,',
)]
class Rename {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
		private readonly IConfig $config,
	) {
	}

	public function __invoke(
		IOutput $output,
		IQuestionHelper $questionHelper,
		#[Argument(description: 'Object store configuration to rename')]
		string $source,
		#[Argument(description: 'New name for the object store configuration')]
		string $target,
	): ExitCode {
		$configs = $this->objectStoreConfig->getObjectStoreConfigs();
		if (!isset($configs[$source])) {
			$output->writeln('<error>Unknown object store configuration: ' . $source . '</error>');
			return ExitCode::Failure;
		}

		if ($source === 'root') {
			$output->writeln('<error>Renaming the root configuration is not supported.</error>');
			return ExitCode::Failure;
		}

		if ($source === 'default') {
			$output->writeln('<error>Renaming the default configuration is not supported.</error>');
			return ExitCode::Failure;
		}

		if (!isset($configs[$target])) {
			$output->writeln('<comment>Target object store configuration ' . $target . ' doesn\'t exist yet.</comment>');
			$output->writeln('The target configuration can be created automatically.');
			$output->writeln('However, as this depends on modifying the config.php, this only works as long as the instance runs on a single node or all nodes in a clustered setup have a shared config file (such as from a shared network mount).');
			$output->writeln('If the different nodes have a separate copy of the config.php file, the automatic object store configuration creation will lead to the configuration going out of sync.');
			$output->writeln('If these requirements are not met, you can manually create the target object store configuration in each node\'s configuration before running the command.');
			$output->writeln('');
			$output->writeln('<error>Failure to check these requirements will lead to data loss for users.</error>');

			$question = new ConfirmationQuestion('Automatically create target object store configuration? [y/N] ', false);
			if ($questionHelper->ask($question)) {
				$configs[$target] = $configs[$source];

				// update all aliases
				foreach ($configs as &$config) {
					if ($config === $source) {
						$config = $target;
					}
				}
				$this->config->setSystemValue('objectstore', $configs);
			} else {
				return ExitCode::Success;
			}
		} elseif (($configs[$source] !== $configs[$target]) || $configs[$source] !== $target) {
			$output->writeln('<error>Source and target configuration differ.</error>');
			$output->writeln('');
			$output->writeln('To ensure proper migration of users, the source and target configuration must be the same to ensure that the objects for the moved users exist on the target configuration.');
			$output->writeln('The usual migration process consists of creating a clone of the old configuration, moving the users from the old configuration to the new one, and then adjust the old configuration that is longer used.');
			return ExitCode::Failure;
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('preferences')
			->set('configvalue', $query->createNamedParameter($target))
			->where($query->expr()->eq('appid', $query->createNamedParameter('homeobjectstore')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('objectstore')))
			->andWhere($query->expr()->eq('configvalue', $query->createNamedParameter($source)));
		$count = $query->executeStatement();

		if ($count > 0) {
			$output->writeln('Moved <info>' . $count . '</info> users');
		} else {
			$output->writeln('No users moved');
		}

		return ExitCode::Success;
	}
}
