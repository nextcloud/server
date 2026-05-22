<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Comments;

use OCP\Comments\ICommentsManager;
use OCP\Comments\ICommentsManagerFactory;
use Psr\Container\ContainerInterface;

class ManagerFactory implements ICommentsManagerFactory {
	public function __construct(
		private ContainerInterface $serverContainer,
	) {
	}

	#[\Override]
	public function getManager(): ICommentsManager {
		return $this->serverContainer->get(Manager::class);
	}
}
