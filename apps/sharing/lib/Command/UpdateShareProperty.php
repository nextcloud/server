<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ShareProperty;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateShareProperty extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-property')
			->setDescription('Update a property of a share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('class', InputArgument::REQUIRED, 'Property class')
			->addArgument('value', InputArgument::OPTIONAL, 'Property value. Omitting it will remove the value.');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		return $this->wrapExecution($output, function () use ($input): string {
			/** @var string $id */
			$id = $input->getArgument('id');
			/** @var class-string<ISharePropertyType> $class */
			$class = $input->getArgument('class');
			/** @var ?string $value */
			$value = $input->getArgument('value');

			$this->manager->updateShareProperty($this->accessContext, $id, new ShareProperty($class, $value));
			return $id;
		});
	}
}
