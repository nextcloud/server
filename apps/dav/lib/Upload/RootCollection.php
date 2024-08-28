<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend;

class RootCollection extends AbstractPrincipalCollection {

	/** @var CleanupService */
	private $cleanupService;

	public function __construct(PrincipalBackend\BackendInterface $principalBackend,
		string $principalPrefix,
		CleanupService $cleanupService) {
		parent::__construct($principalBackend, $principalPrefix);
		$this->cleanupService = $cleanupService;
	}

	/**
	 * @inheritdoc
	 */
	public function getChildForPrincipal(array $principalInfo): UploadHome {
		return new UploadHome($principalInfo, $this->cleanupService);
	}

	/**
	 * @inheritdoc
	 */
	public function getName(): string {
		return 'uploads';
	}
}
