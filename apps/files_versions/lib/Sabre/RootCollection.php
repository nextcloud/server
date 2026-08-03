<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Sabre;

use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUserSession;
use Sabre\DAV\INode;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend;

class RootCollection extends AbstractPrincipalCollection {

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		private readonly IRootFolder $rootFolder,
		IConfig $config,
		private readonly IUserManager $userManager,
		private readonly IVersionManager $versionManager,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($principalBackend, 'principals/users');

		$this->disableListing = !$config->getSystemValue('debug', false);
	}

	#[\Override]
	public function getChildForPrincipal(array $principalInfo): VersionHome {
		[, $name] = \Sabre\Uri\split($principalInfo['uri']);
		$user = $this->userSession->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
		return new VersionHome($principalInfo, $this->rootFolder, $this->userManager, $this->versionManager);
	}

	#[\Override]
	public function getName(): string {
		return 'versions';
	}
}
