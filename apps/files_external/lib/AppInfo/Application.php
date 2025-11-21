<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_External\Config\ConfigAdapter;
use OCA\Files_External\Config\UserPlaceholderHandler;
use OCA\Files_External\ConfigLexicon;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
use OCA\Files_External\Lib\Auth\AmazonS3\AccessKey;
use OCA\Files_External\Lib\Auth\Builtin;
use OCA\Files_External\Lib\Auth\NullMechanism;
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
use OCA\Files_External\Service\MountCacheService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\QueryException;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;

/**
 * @package OCA\Files_External\AppInfo
 */
class Application extends App implements IBackendProvider, IAuthMechanismProvider, IBootstrap {
	public const APP_ID = 'files_external';

	/**
	 * Application constructor.
	 *
	 * @throws QueryException
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalListener::class);
		$context->registerEventListener(StorageCreatedEvent::class, MountCacheService::class);
		$context->registerEventListener(StorageDeletedEvent::class, MountCacheService::class);
		$context->registerEventListener(StorageUpdatedEvent::class, MountCacheService::class);
		$context->registerEventListener(BeforeGroupDeletedEvent::class, MountCacheService::class);
		$context->registerEventListener(UserCreatedEvent::class, MountCacheService::class);
		$context->registerEventListener(UserAddedEvent::class, MountCacheService::class);
		$context->registerEventListener(UserRemovedEvent::class, MountCacheService::class);

		$context->registerConfigLexicon(ConfigLexicon::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (IMountProviderCollection $mountProviderCollection, ConfigAdapter $configAdapter): void {
			$mountProviderCollection->registerProvider($configAdapter);
		});
		$context->injectFn(function (BackendService $backendService, UserPlaceholderHandler $userConfigHandler): void {
			$backendService->registerBackendProvider($this);
			$backendService->registerAuthMechanismProvider($this);
			$backendService->registerConfigHandler('user', function () use ($userConfigHandler) {
				return $userConfigHandler;
			});
		});
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
