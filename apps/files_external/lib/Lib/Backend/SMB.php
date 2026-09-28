<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Lib\Backend;

use Icewind\SMB\BasicAuth;
use Icewind\SMB\KerberosAuth;
use Icewind\SMB\KerberosTicket;
use Icewind\SMB\Native\NativeServer;
use Icewind\SMB\Wrapped\Server;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\Auth\SMB\KerberosApacheAuth as KerberosApacheAuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\MissingDependency;
use OCA\Files_External\Lib\Storage\SystemBridge;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IL10N;
use OCP\IUser;

class SMB extends Backend {
	public function __construct(IL10N $l, Password $legacyAuth) {
		$this
			->setIdentifier('smb')
			->addIdentifierAlias('\OC\Files\Storage\SMB')// legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\SMB')
			->setText($l->t('SMB/CIFS'))
			->addParameters([
				new DefinitionParameter('host', $l->t('Host')),
				new DefinitionParameter('share', $l->t('Share')),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('domain', $l->t('Domain')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('show_hidden', $l->t('Show hidden files')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('case_sensitive', $l->t('Case sensitive file system')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL)
					->setDefaultValue(true)
					->setTooltip($l->t('Disabling it will allow to use a case insensitive file system, but comes with a performance penalty')),
				(new DefinitionParameter('check_acl', $l->t('Verify ACL access when listing files')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL)
					->setTooltip($l->t("Check the ACL's of each file or folder inside a directory to filter out items where the account has no read permissions, comes with a performance penalty")),
				(new DefinitionParameter('timeout', $l->t('Timeout')))
					->setType(DefinitionParameter::VALUE_TEXT)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL)
					->setFlag(DefinitionParameter::FLAG_HIDDEN),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->addAuthScheme(AuthMechanism::SCHEME_SMB)
			->setLegacyAuthMechanism($legacyAuth);
	}

	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null): void {
		$auth = $storage->getAuthMechanism();
		if ($auth->getScheme() === AuthMechanism::SCHEME_PASSWORD) {
			if (!is_string($storage->getBackendOption('user')) || !is_string($storage->getBackendOption('password'))) {
				throw new \InvalidArgumentException('user or password is not set');
			}

			$smbAuth = new BasicAuth(
				$storage->getBackendOption('user'),
				$storage->getBackendOption('domain'),
				$storage->getBackendOption('password')
			);
		} else {
			switch ($auth->getIdentifier()) {
				case 'smb::kerberos':
					$smbAuth = new KerberosAuth();
					break;
				case 'smb::kerberosapache':
					if (!$auth instanceof KerberosApacheAuthMechanism) {
						throw new \InvalidArgumentException('invalid authentication backend');
					}
					$credentialsStore = $auth->getCredentialsStore();
					$kerbAuth = new KerberosAuth();
					$kerbAuth->setTicket(KerberosTicket::fromEnv());
					// check if a kerberos ticket is available, else fallback to session credentials
					if ($kerbAuth->getTicket()?->isValid()) {
						$smbAuth = $kerbAuth;
					} else {
						try {
							$credentials = $credentialsStore->getLoginCredentials();
							$loginName = $credentials->getLoginName();
							$pass = $credentials->getPassword();
							preg_match('/(.*)@(.*)/', $loginName, $matches);
							$realm = $storage->getBackendOption('default_realm');
							if (empty($realm)) {
								$realm = 'WORKGROUP';
							}
							if (count($matches) === 0) {
								$username = $loginName;
								$workgroup = $realm;
							} else {
								[, $username, $workgroup] = $matches;
							}
							$smbAuth = new BasicAuth(
								$username,
								$workgroup,
								$pass
							);
						} catch (\Exception) {
							throw new InsufficientDataForMeaningfulAnswerException('No session credentials saved');
						}
					}

					break;
				default:
					throw new \InvalidArgumentException('unknown authentication backend');
			}
		}

		$storage->setBackendOption('auth', $smbAuth);
	}

	public function checkDependencies(): array {
		$system = \OCP\Server::get(SystemBridge::class);
		if (NativeServer::available($system)) {
			return [];
		} elseif (Server::available($system)) {
			$missing = new MissingDependency('php-smbclient');
			$missing->setOptional(true);
			$missing->setMessage('The php-smbclient library provides improved compatibility and performance for SMB storages.');
			return [$missing];
		} else {
			$missing = new MissingDependency('php-smbclient');
			$missing->setMessage('Either the php-smbclient library (preferred) or the smbclient binary is required for SMB storages.');
			return [$missing, new MissingDependency('smbclient')];
		}
	}
}
