<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Server;
use Sabre\DAV\INode;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend;

class RootCollection extends AbstractPrincipalCollection {
	public function __construct(
		private readonly ITrashManager $trashManager,
		PrincipalBackend\BackendInterface $principalBackend,
		IConfig $config,
	) {
		parent::__construct($principalBackend, 'principals/users');
		$this->disableListing = !$config->getSystemValue('debug', false);
	}

	#[\Override]
	public function getChildForPrincipal(array $principalInfo): TrashHome {
		[, $name] = \Sabre\Uri\split($principalInfo['uri']);
		$user = Server::get(IUserSession::class)->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
		return new TrashHome($principalInfo, $this->trashManager, $user);
	}

	#[\Override]
	public function getName(): string {
		return 'trashbin';
	}
}
