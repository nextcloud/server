<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use NCU\Sharing\Permission\ISharePermissionType;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Recipient\IShareRecipientType;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateShareRecipientPermission extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-recipient-permission')
			->setDescription('Update a permission for a recipient of a share.')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('permission-class', InputArgument::REQUIRED, 'Permission class')
			->addArgument('permission-enabled', InputArgument::REQUIRED, 'Permission enabled. Only takes "true" or "false".')
			->addArgument('recipient-class', InputArgument::REQUIRED, 'Recipient class')
			->addArgument('recipient-value', InputArgument::REQUIRED, 'Recipient value')
			->addArgument('recipient-instance', InputArgument::OPTIONAL, 'Recipient instance');
		parent::configure();
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');
		/** @var class-string<ISharePermissionType> $permissionClass */
		$permissionClass = $input->getArgument('permission-class');
		/** @var string $permissionEnabled */
		$permissionEnabled = $input->getArgument('permission-enabled');
		$permissionEnabled = $permissionEnabled === 'true';
		/** @var class-string<IShareRecipientType> $recipientClass */
		$recipientClass = $input->getArgument('recipient-class');
		/** @var non-empty-string $recipientValue */
		$recipientValue = $input->getArgument('recipient-value');
		/** @var ?non-empty-string $recipientInstance */
		$recipientInstance = $input->getArgument('recipient-instance');

		$recipient = new ShareRecipient($recipientClass, $recipientValue, $recipientInstance);
		$permission = new SharePermission($permissionClass, $permissionEnabled);

		return $this->wrapExecution($input, $output, function () use ($id, $recipient, $permission): Share {
			$share = $this->manager->getShare($this->accessContext, $id);
			return $this->manager->updateShareRecipientPermission($this->accessContext, $share, $recipient, $permission);
		});
	}
}
