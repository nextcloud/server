<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\Permission\SharePermissionPreset;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SelectSharePermissionPreset extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:select-share-permission-preset')
			->setDescription('Select a permission preset for a share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('permission-preset', InputArgument::REQUIRED, 'Permission preset');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');
		/** @var string $permissionPreset */
		$permissionPreset = $input->getArgument('permission-preset');
		$permissionPreset = SharePermissionPreset::from($permissionPreset);

		return $this->wrapExecution($output, function () use ($id, $permissionPreset): string {
			$this->manager->selectSharePermissionPreset($this->accessContext, $id, $permissionPreset);
			return $id;
		});
	}
}
