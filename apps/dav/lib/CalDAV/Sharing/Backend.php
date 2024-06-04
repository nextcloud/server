<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Sharing;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend as SharingBackend;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class Backend extends SharingBackend {

	public function __construct(private IUserManager $userManager,
		private IGroupManager $groupManager,
		private Principal $principalBackend,
		private ICacheFactory $cacheFactory,
		private Service $service,
		private LoggerInterface $logger,
	) {
		parent::__construct($this->userManager, $this->groupManager, $this->principalBackend, $this->cacheFactory, $this->service, $this->logger);
	}
}
