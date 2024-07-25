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

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserManager */
	private $userManager;

	/** @var IVersionManager */
	private $versionManager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(
		PrincipalBackend\BackendInterface $principalBackend,
		IRootFolder $rootFolder,
		IConfig $config,
		IUserManager $userManager,
		IVersionManager $versionManager,
		IUserSession $userSession
	) {
		parent::__construct($principalBackend, 'principals/users');

		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->versionManager = $versionManager;
		$this->userSession = $userSession;

		$this->disableListing = !$config->getSystemValue('debug', false);
	}

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principalInfo
	 * @return INode
	 */
	public function getChildForPrincipal(array $principalInfo) {
		[, $name] = \Sabre\Uri\split($principalInfo['uri']);
		$user = $this->userSession->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
		return new VersionHome($principalInfo, $this->rootFolder, $this->userManager, $this->versionManager);
	}

	public function getName() {
		return 'versions';
	}
}
