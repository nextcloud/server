<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib\Auth\SMB;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCP\IL10N;

class KerberosAuth extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('smb::kerberos')
			->setScheme(self::SCHEME_SMB)
			->setText($l->t('Kerberos ticket'));
	}
}
