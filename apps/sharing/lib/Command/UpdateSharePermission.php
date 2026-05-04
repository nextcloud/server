<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateSharePermission extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-permission')
			->setDescription('Update a permission of a share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('class', InputArgument::REQUIRED, 'Permission class')
			->addArgument('enabled', InputArgument::REQUIRED, 'Permission enabled. Only takes "true" or "false".');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		return $this->wrapExecution($output, function () use ($input): string {
			/** @var string $id */
			$id = $input->getArgument('id');
			/** @var class-string<ISharePermissionType> $class */
			$class = $input->getArgument('class');
			/** @var string $enabled */
			$enabled = $input->getArgument('enabled');
			$enabled = $enabled === 'true';

			$this->manager->updateSharePermission($this->accessContext, $id, new SharePermission($class, $enabled));
			return $id;
		});
	}
}
