<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AddShareRecipient extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:add-share-recipient')
			->setDescription('Add a recipient to a share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('class', InputArgument::REQUIRED, 'Recipient class')
			->addArgument('value', InputArgument::REQUIRED, 'Recipient value');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		return $this->wrapExecution($output, function () use ($input): string {
			/** @var string $id */
			$id = $input->getArgument('id');
			/** @var class-string<IShareRecipientType> $class */
			$class = $input->getArgument('class');
			/** @var non-empty-string $value */
			$value = $input->getArgument('value');

			$this->manager->addShareRecipient($this->accessContext, $id, new ShareRecipient($class, $value));
			return $id;
		});
	}
}
