<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\Files_External\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Config\UserPlaceholderHandler;
use OCA\Files_External\Lib\Auth\AmazonS3\AccessKey;
use OCA\Files_External\Lib\Auth\Builtin;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\Auth\OAuth1\OAuth1;
use OCA\Files_External\Lib\Auth\OAuth2\OAuth2;
use OCA\Files_External\Lib\Auth\OpenStack\OpenStackV2;
use OCA\Files_External\Lib\Auth\OpenStack\OpenStackV3;
use OCA\Files_External\Lib\Auth\OpenStack\Rackspace;
use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\Auth\Password\LoginCredentials;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\Auth\Password\SessionCredentials;
use OCA\Files_External\Lib\Auth\Password\UserGlobalAuth;
use OCA\Files_External\Lib\Auth\Password\UserProvided;
use OCA\Files_External\Lib\Auth\PublicKey\RSA;
use OCA\Files_External\Lib\Auth\PublicKey\RSAPrivateKey;
use OCA\Files_External\Lib\Auth\SMB\KerberosApacheAuth;
use OCA\Files_External\Lib\Auth\SMB\KerberosAuth;
use OCA\Files_External\Lib\Backend\AmazonS3;
use OCA\Files_External\Lib\Backend\DAV;
use OCA\Files_External\Lib\Backend\FTP;
use OCA\Files_External\Lib\Backend\Local;
use OCA\Files_External\Lib\Backend\OwnCloud;
use OCA\Files_External\Lib\Backend\SFTP;
use OCA\Files_External\Lib\Backend\SFTP_Key;
use OCA\Files_External\Lib\Backend\SMB;
use OCA\Files_External\Lib\Backend\SMB_OC;
use OCA\Files_External\Lib\Backend\Swift;
use OCA\Files_External\Lib\Config\IAuthMechanismProvider;
use OCA\Files_External\Lib\Config\IBackendProvider;
use OCA\Files_External\Listener\GroupDeletedListener;
use OCA\Files_External\Listener\LoadAdditionalListener;
use OCA\Files_External\Listener\UserDeletedListener;
use OCA\Files_External\Service\BackendService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\User\Events\UserDeletedEvent;

require_once __DIR__ . '/../../3rdparty/autoload.php';

/**
 * @package OCA\Files_External\AppInfo
 */
class Application extends App implements IBackendProvider, IAuthMechanismProvider, IBootstrap {
	public const APP_ID = 'files_external';

	/**
	 * Application constructor.
	 *
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IMountProviderCollection $mountProviderCollection, ConfigAdapter $configAdapter) {
			$mountProviderCollection->registerProvider($configAdapter);
		});
		$context->injectFn(function (BackendService $backendService, UserPlaceholderHandler $userConfigHandler) {
			$backendService->registerBackendProvider($this);
			$backendService->registerAuthMechanismProvider($this);
			$backendService->registerConfigHandler('user', function () use ($userConfigHandler) {
				return $userConfigHandler;
			});
		});

		// force-load auth mechanisms since some will register hooks
		// TODO: obsolete these and use the TokenProvider to get the user's password from the session
		$this->getAuthMechanisms();
	}

	/**
	 * @{inheritdoc}
	 */
	public function getBackends() {
		$container = $this->getContainer();

		$backends = [
			$container->get(Local::class),
			$container->get(FTP::class),
			$container->get(DAV::class),
			$container->get(OwnCloud::class),
			$container->get(SFTP::class),
			$container->get(AmazonS3::class),
			$container->get(Swift::class),
			$container->get(SFTP_Key::class),
			$container->get(SMB::class),
			$container->get(SMB_OC::class),
		];

		return $backends;
	}

	/**
	 * @{inheritdoc}
	 */
	public function getAuthMechanisms() {
		$container = $this->getContainer();

		return [
			// AuthMechanism::SCHEME_NULL mechanism
			$container->get(NullMechanism::class),

			// AuthMechanism::SCHEME_BUILTIN mechanism
			$container->get(Builtin::class),

			// AuthMechanism::SCHEME_PASSWORD mechanisms
			$container->get(Password::class),
			$container->get(SessionCredentials::class),
			$container->get(LoginCredentials::class),
			$container->get(UserProvided::class),
			$container->get(GlobalAuth::class),
			$container->get(UserGlobalAuth::class),

			// AuthMechanism::SCHEME_OAUTH1 mechanisms
			$container->get(OAuth1::class),

			// AuthMechanism::SCHEME_OAUTH2 mechanisms
			$container->get(OAuth2::class),

			// AuthMechanism::SCHEME_PUBLICKEY mechanisms
			$container->get(RSA::class),
			$container->get(RSAPrivateKey::class),

			// AuthMechanism::SCHEME_OPENSTACK mechanisms
			$container->get(OpenStackV2::class),
			$container->get(OpenStackV3::class),
			$container->get(Rackspace::class),

			// Specialized mechanisms
			$container->get(AccessKey::class),
			$container->get(KerberosAuth::class),
			$container->get(KerberosApacheAuth::class),
		];
	}
}
