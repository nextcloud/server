<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib\Backend;

use Icewind\SMB\BasicAuth;
use Icewind\SMB\KerberosApacheAuth;
use Icewind\SMB\KerberosAuth;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\Auth\SMB\KerberosApacheAuth as KerberosApacheAuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IL10N;
use OCP\IUser;

class SMB extends Backend {
	use LegacyDependencyCheckPolyfill;

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
					->setTooltip($l->t("Check the ACL's of each file or folder inside a directory to filter out items where the user has no read permissions, comes with a performance penalty")),
				(new DefinitionParameter('timeout', $l->t('Timeout')))
					->setType(DefinitionParameter::VALUE_HIDDEN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->addAuthScheme(AuthMechanism::SCHEME_SMB)
			->setLegacyAuthMechanism($legacyAuth);
	}

	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null) {
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
					$kerbAuth = new KerberosApacheAuth();
					// check if a kerberos ticket is available, else fallback to session credentials
					if ($kerbAuth->checkTicket()) {
						$smbAuth = $kerbAuth;
					} else {
						try {
							$credentials = $credentialsStore->getLoginCredentials();
							$user = $credentials->getLoginName();
							$pass = $credentials->getPassword();
							preg_match('/(.*)@(.*)/', $user, $matches);
							$realm = $storage->getBackendOption('default_realm');
							if (empty($realm)) {
								$realm = 'WORKGROUP';
							}
							if (count($matches) === 0) {
								$username = $user;
								$workgroup = $realm;
							} else {
								$username = $matches[1];
								$workgroup = $matches[2];
							}
							$smbAuth = new BasicAuth(
								$username,
								$workgroup,
								$pass
							);
						} catch (\Exception $e) {
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
}
