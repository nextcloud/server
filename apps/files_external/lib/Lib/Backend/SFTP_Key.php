<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

class SFTP_Key extends Backend {
	public function __construct(IL10N $l, RSA $legacyAuth, SFTP $sftpBackend) {
		$this
			->setIdentifier('\OC\Files\Storage\SFTP_Key')
			->setStorageClass('\OCA\Files_External\Lib\Storage\SFTP')
			->setText($l->t('SFTP with secret key login'))
			->addParameters([
				new DefinitionParameter('host', $l->t('Host')),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PUBLICKEY)
			->setLegacyAuthMechanism($legacyAuth)
			->deprecateTo($sftpBackend)
		;
	}
}
