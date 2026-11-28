<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Sharing;

use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\Backend as SharingBackend;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;

class Backend extends SharingBackend {

	public function __construct(
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private Principal $principalBackend,
		private RemoteUserPrincipalBackend $remoteUserPrincipalBackend,
		private ICacheFactory $cacheFactory,
		private Service $service,
		private FederationSharingService $federationSharingService,
		private LoggerInterface $logger,
		private IShareManager $shareManager,
		private IUserSession $userSession,
	) {
		parent::__construct($this->userManager, $this->groupManager, $this->principalBackend, $this->remoteUserPrincipalBackend, $this->cacheFactory, $this->service, $this->federationSharingService, $this->logger, $this->shareManager, $this->userSession);
	}
}
