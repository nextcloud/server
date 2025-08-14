<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend;

class RootCollection extends AbstractPrincipalCollection {

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		string $principalPrefix,
		private CleanupService $cleanupService,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
	) {
		parent::__construct($principalBackend, $principalPrefix);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildForPrincipal(array $principalInfo): UploadHome {
		return new UploadHome($principalInfo, $this->cleanupService, $this->rootFolder, $this->userSession);
	}

	/**
	 * @inheritdoc
	 */
	public function getName(): string {
		return 'uploads';
	}
}
