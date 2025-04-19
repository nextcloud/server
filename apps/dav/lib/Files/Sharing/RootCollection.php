<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Files\Sharing;

use OC\Files\View;
use Sabre\DAV\INode;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

class RootCollection extends AbstractPrincipalCollection {
	public function __construct(
		private View $view,
		private INode $root,
		BackendInterface $principalBackend,
		string $principalPrefix = 'principals',
	) {
		parent::__construct($principalBackend, $principalPrefix);
	}

	public function getChildForPrincipal(array $principalInfo): INode {
		return $this->root;
	}

	public function getName() {
		return 'files';
	}
}
