<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use NCU\Sharing\Share;
use NCU\Sharing\Source\IShareSourceType;
use NCU\Sharing\Source\ShareSource;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RemoveShareSource extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:remove-share-source')
			->setDescription('Remove an existing source from a share.')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('class', InputArgument::REQUIRED, 'Source class')
			->addArgument('value', InputArgument::REQUIRED, 'Source value');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');
		/** @var class-string<IShareSourceType> $class */
		$class = $input->getArgument('class');
		/** @var non-empty-string $value */
		$value = $input->getArgument('value');

		return $this->wrapExecution($output, function () use ($id, $class, $value): Share {
			$share = $this->manager->getShare($this->accessContext, $id);
			return $this->manager->removeShareSource($this->accessContext, $share, new ShareSource($class, $value));
		});
	}
}
