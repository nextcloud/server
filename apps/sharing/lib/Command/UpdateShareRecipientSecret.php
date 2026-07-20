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

final class UpdateShareRecipientSecret extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-recipient-secret')
			->setDescription('Update the scecret of a recipient.')
			->addArgument('secret', InputArgument::REQUIRED, 'Recipient secret')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('class', InputArgument::REQUIRED, 'Recipient class')
			->addArgument('value', InputArgument::REQUIRED, 'Recipient value')
			->addArgument('instance', InputArgument::OPTIONAL, 'Recipient instance');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');
		/** @var class-string<IShareRecipientType> $class */
		$class = $input->getArgument('class');
		/** @var non-empty-string $value */
		$value = $input->getArgument('value');
		/** @var ?non-empty-string $instance */
		$instance = $input->getArgument('instance');
		/** @var non-empty-string $secret */
		$secret = $input->getArgument('secret');

		return $this->wrapExecution($output, function () use ($id, $class, $value, $instance, $secret): string {
			$this->manager->updateShareRecipientSecret($this->accessContext, $id, new ShareRecipient($class, $value, $instance), $secret);
			return $id;
		});
	}
}
