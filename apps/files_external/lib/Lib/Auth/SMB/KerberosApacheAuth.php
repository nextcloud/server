<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Lib\Auth\SMB;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\IL10N;

class KerberosApacheAuth extends AuthMechanism {
	public function __construct(
		IL10N $l,
		private IStore $credentialsStore,
	) {
		$realm = new DefinitionParameter('default_realm', 'Default realm');
		$realm
			->setType(DefinitionParameter::VALUE_TEXT)
			->setFlag(DefinitionParameter::FLAG_OPTIONAL)
			->setTooltip($l->t('Kerberos default realm, defaults to "WORKGROUP"'));
		$this
			->setIdentifier('smb::kerberosapache')
			->setScheme(self::SCHEME_SMB)
			->setText($l->t('Kerberos ticket Apache mode'))
			->addParameter($realm);
	}

	public function getCredentialsStore(): IStore {
		return $this->credentialsStore;
	}
}
